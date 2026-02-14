/**
 * =============================================================================
 * WEDEV UI - Front-Office (minimal)
 * =============================================================================
 * Utilitaires JavaScript légers pour le front-office PrestaShop.
 * Conçu pour être minimal et performant.
 *
 * Usage:
 * <script src="{{ $urls.base_url }}modules/modulename/src/Extension/UI/Assets/js/front/wedev-front.js"></script>
 *
 * Puis dans votre code:
 * WedevFront.ajax(url, data).then(response => { ... });
 * =============================================================================
 */

const WedevFront = {
    /**
     * Version
     */
    version: '1.0.0',

    /**
     * Requête AJAX simple avec gestion automatique du token PrestaShop
     *
     * @param {string} url - URL de la requête
     * @param {Object} data - Données à envoyer
     * @param {Object} options - Options supplémentaires
     * @returns {Promise<any>}
     *
     * @example
     * const result = await WedevFront.ajax(
     *     prestashop.urls.base_url + 'module/mymodule/action',
     *     { id_product: 123 }
     * );
     */
    async ajax(url, data = {}, options = {}) {
        const formData = new FormData();

        // Ajouter les données
        Object.entries(data).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        // Ajouter le token PrestaShop si disponible
        if (window.prestashop?.static_token) {
            formData.append('token', window.prestashop.static_token);
        }

        // Options de fetch
        const fetchOptions = {
            method: options.method || 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType?.includes('application/json')) {
                return response.json();
            }

            return response.text();
        } catch (error) {
            console.error('WedevFront Ajax Error:', error);
            throw error;
        }
    },

    /**
     * Lazy loading d'éléments quand ils deviennent visibles
     *
     * @param {string} selector - Sélecteur CSS des éléments à observer
     * @param {Function} callback - Fonction appelée quand l'élément est visible
     * @param {Object} options - Options de l'IntersectionObserver
     *
     * @example
     * WedevFront.lazyLoad('.lazy-image', (img) => {
     *     img.src = img.dataset.src;
     *     img.classList.add('loaded');
     * });
     */
    lazyLoad(selector, callback, options = {}) {
        const observerOptions = {
            root: options.root || null,
            rootMargin: options.rootMargin || '50px',
            threshold: options.threshold || 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll(selector).forEach(el => observer.observe(el));

        return observer;
    },

    /**
     * Debounce - Limite la fréquence d'appel d'une fonction
     *
     * @param {Function} func - Fonction à debouncer
     * @param {number} wait - Délai en ms
     * @returns {Function}
     *
     * @example
     * const debouncedSearch = WedevFront.debounce((query) => {
     *     console.log('Search:', query);
     * }, 300);
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle - Limite le nombre d'appels d'une fonction par intervalle
     *
     * @param {Function} func - Fonction à throttler
     * @param {number} limit - Intervalle minimum en ms
     * @returns {Function}
     */
    throttle(func, limit = 100) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Format de prix selon le contexte PrestaShop
     *
     * @param {number} price - Prix à formater
     * @param {string} currencySign - Symbole de devise (défaut: depuis PS)
     * @returns {string}
     */
    formatPrice(price, currencySign = null) {
        const sign = currencySign || window.prestashop?.currency?.sign || '€';
        const format = window.prestashop?.currency?.format || '%s %price%';
        
        const formattedPrice = price.toFixed(2);
        
        return format
            .replace('%s', sign)
            .replace('%price%', formattedPrice);
    },

    /**
     * Ajouter au panier via AJAX
     *
     * @param {number} idProduct - ID du produit
     * @param {number} idProductAttribute - ID de la déclinaison (optionnel)
     * @param {number} quantity - Quantité
     * @returns {Promise<Object>}
     */
    async addToCart(idProduct, idProductAttribute = 0, quantity = 1) {
        const url = window.prestashop?.urls?.actions?.add_to_cart;
        
        if (!url) {
            throw new Error('PrestaShop cart URL not available');
        }

        return this.ajax(url, {
            id_product: idProduct,
            id_product_attribute: idProductAttribute,
            qty: quantity,
            add: 1,
            action: 'add'
        });
    },

    /**
     * Scroll fluide vers un élément
     *
     * @param {string|Element} target - Sélecteur ou élément
     * @param {number} offset - Offset en pixels (pour header fixe)
     */
    scrollTo(target, offset = 0) {
        const element = typeof target === 'string' 
            ? document.querySelector(target) 
            : target;

        if (element) {
            const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    },

    /**
     * Copier du texte dans le presse-papier
     *
     * @param {string} text - Texte à copier
     * @returns {Promise<boolean>}
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (error) {
            // Fallback pour navigateurs plus anciens
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                return true;
            } catch {
                return false;
            } finally {
                document.body.removeChild(textarea);
            }
        }
    },

    /**
     * Détecter si l'appareil est mobile
     *
     * @returns {boolean}
     */
    isMobile() {
        return window.innerWidth < 768 || 
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },

    /**
     * Obtenir un paramètre d'URL
     *
     * @param {string} name - Nom du paramètre
     * @param {string} url - URL (défaut: URL actuelle)
     * @returns {string|null}
     */
    getUrlParam(name, url = window.location.href) {
        const params = new URL(url).searchParams;
        return params.get(name);
    },

    /**
     * Stocker/récupérer des données en localStorage avec expiration
     *
     * @param {string} key - Clé
     * @param {any} value - Valeur (si undefined, lecture seule)
     * @param {number} ttl - Time-to-live en secondes (optionnel)
     * @returns {any}
     */
    storage(key, value = undefined, ttl = null) {
        const prefixedKey = `wedev_${key}`;

        // Lecture
        if (value === undefined) {
            const item = localStorage.getItem(prefixedKey);
            if (!item) return null;

            try {
                const { value: storedValue, expiry } = JSON.parse(item);
                if (expiry && Date.now() > expiry) {
                    localStorage.removeItem(prefixedKey);
                    return null;
                }
                return storedValue;
            } catch {
                return item;
            }
        }

        // Suppression
        if (value === null) {
            localStorage.removeItem(prefixedKey);
            return null;
        }

        // Écriture
        const item = {
            value,
            expiry: ttl ? Date.now() + (ttl * 1000) : null
        };
        localStorage.setItem(prefixedKey, JSON.stringify(item));
        return value;
    }
};

// Exposer globalement
window.WedevFront = WedevFront;

// Log d'initialisation
console.log(`WedevFront v${WedevFront.version} loaded`);





