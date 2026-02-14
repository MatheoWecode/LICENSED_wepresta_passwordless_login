/**
 * =============================================================================
 * WEDEV UI - AJAX Utilities
 * =============================================================================
 * Wrapper léger autour de fetch avec gestion des erreurs PrestaShop.
 * Compatible avec les tokens CSRF et les réponses JSON PrestaShop.
 * =============================================================================
 */

export const WedevAjax = {
    /**
     * Configuration par défaut
     */
    config: {
        timeout: 30000,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    },

    /**
     * Requête GET
     * @param {string} url - URL de la requête
     * @param {Object} params - Paramètres de query string
     * @returns {Promise<any>}
     */
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;

        return this._request(fullUrl, { method: 'GET' });
    },

    /**
     * Requête POST (FormData ou JSON)
     * @param {string} url - URL de la requête
     * @param {Object|FormData} data - Données à envoyer
     * @param {boolean} asJson - Envoyer en JSON (défaut: false, envoie en FormData)
     * @returns {Promise<any>}
     */
    async post(url, data, asJson = false) {
        const options = {
            method: 'POST',
            body: asJson ? JSON.stringify(data) : this._toFormData(data),
        };

        if (asJson) {
            options.headers = { 'Content-Type': 'application/json' };
        }

        return this._request(url, options);
    },

    /**
     * Requête PUT
     * @param {string} url - URL de la requête
     * @param {Object} data - Données à envoyer
     * @returns {Promise<any>}
     */
    async put(url, data) {
        return this._request(url, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' }
        });
    },

    /**
     * Requête DELETE
     * @param {string} url - URL de la requête
     * @param {Object} params - Paramètres optionnels
     * @returns {Promise<any>}
     */
    async delete(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;

        return this._request(fullUrl, { method: 'DELETE' });
    },

    /**
     * Requête POST avec gestion automatique du token CSRF PrestaShop
     * @param {string} url - URL de la requête
     * @param {Object} data - Données à envoyer
     * @param {string} tokenInputName - Nom du champ token (défaut: 'token')
     * @returns {Promise<any>}
     */
    async postWithToken(url, data, tokenInputName = 'token') {
        // Récupérer le token depuis le DOM ou l'objet prestashop global
        const token = this._getToken(tokenInputName);

        return this.post(url, { ...data, [tokenInputName]: token });
    },

    /**
     * Upload de fichier avec suivi de progression
     * @param {string} url - URL d'upload
     * @param {FormData} formData - FormData avec le fichier
     * @param {Function} onProgress - Callback de progression (0-100)
     * @returns {Promise<any>}
     */
    async upload(url, formData, onProgress = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.open('POST', url);

            // Headers
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Progression
            if (onProgress && xhr.upload) {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        onProgress(percent);
                    }
                });
            }

            // Réponse
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch {
                        resolve(xhr.responseText);
                    }
                } else {
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            };

            // Erreur
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.ontimeout = () => reject(new Error('Request timeout'));

            // Timeout
            xhr.timeout = this.config.timeout;

            // Envoi
            xhr.send(formData);
        });
    },

    /**
     * Requête interne avec gestion des erreurs
     * @private
     */
    async _request(url, options) {
        options.headers = {
            ...this.config.headers,
            ...options.headers
        };

        // Timeout via AbortController
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
        options.signal = controller.signal;

        try {
            const response = await fetch(url, options);

            clearTimeout(timeoutId);

            // Erreur HTTP
            if (!response.ok) {
                const error = new Error(`HTTP ${response.status}: ${response.statusText}`);
                error.status = response.status;
                error.response = response;
                throw error;
            }

            // Parse de la réponse
            const contentType = response.headers.get('content-type');
            if (contentType?.includes('application/json')) {
                return response.json();
            }

            return response.text();

        } catch (error) {
            clearTimeout(timeoutId);

            // Erreur d'abort (timeout)
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }

            console.error('WEDEV Ajax Error:', error);
            throw error;
        }
    },

    /**
     * Convertir un objet en FormData
     * @private
     */
    _toFormData(data) {
        if (data instanceof FormData) {
            return data;
        }

        const formData = new FormData();

        Object.entries(data).forEach(([key, value]) => {
            if (value === null || value === undefined) {
                return;
            }

            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item);
                });
            } else if (typeof value === 'object' && !(value instanceof File)) {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value);
            }
        });

        return formData;
    },

    /**
     * Récupérer le token CSRF PrestaShop
     * @private
     */
    _getToken(inputName = 'token') {
        // 1. Depuis un input hidden
        const input = document.querySelector(`input[name="${inputName}"]`);
        if (input) {
            return input.value;
        }

        // 2. Depuis l'objet global prestashop (admin)
        if (window.prestashop?.token) {
            return window.prestashop.token;
        }

        // 3. Depuis une meta tag
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }

        // 4. Depuis les données de page PrestaShop front
        if (window.prestashop?.static_token) {
            return window.prestashop.static_token;
        }

        console.warn('WEDEV Ajax: CSRF token not found');
        return '';
    }
};

// Exposer globalement pour usage sans module bundler
if (typeof window !== 'undefined') {
    window.WedevAjax = WedevAjax;
}

export default WedevAjax;





