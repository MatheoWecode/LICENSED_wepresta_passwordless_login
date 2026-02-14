/**
 * Module Starter - JavaScript Front-Office
 */

(function() {
    'use strict';

    /**
     * Module Starter Frontend
     */
    const Wepresta_Passwordless_Login = {
        /**
         * Configuration
         */
        config: {
            debug: false,
            selectors: {
                container: '.wepresta_passwordless_login-container',
                btn: '.wepresta_passwordless_login-btn',
            }
        },

        /**
         * Initialisation
         */
        init() {
            if (this.config.debug) {
                console.log('[Wepresta_Passwordless_Login] Initializing...');
            }

            this.bindEvents();
            this.onReady();
        },

        /**
         * Liaison des événements
         */
        bindEvents() {
            // Exemple: tracking des clics sur les boutons
            document.querySelectorAll(this.config.selectors.btn).forEach(btn => {
                btn.addEventListener('click', this.onButtonClick.bind(this));
            });
        },

        /**
         * Callback au chargement
         */
        onReady() {
            // Actions au chargement de la page
            const containers = document.querySelectorAll(this.config.selectors.container);
            
            if (containers.length > 0 && this.config.debug) {
                console.log('[Wepresta_Passwordless_Login] Found', containers.length, 'container(s)');
            }
        },

        /**
         * Gestionnaire de clic sur bouton
         * @param {Event} event
         */
        onButtonClick(event) {
            if (this.config.debug) {
                console.log('[Wepresta_Passwordless_Login] Button clicked', event.target);
            }

            // Exemple d'animation
            event.target.classList.add('is-loading');
        },

        /**
         * Méthode utilitaire: requête AJAX
         * @param {string} url
         * @param {Object} data
         * @returns {Promise}
         */
        async ajax(url, data = {}) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return await response.json();
            } catch (error) {
                console.error('[Wepresta_Passwordless_Login] AJAX Error:', error);
                throw error;
            }
        }
    };

    // Initialisation au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Wepresta_Passwordless_Login.init());
    } else {
        Wepresta_Passwordless_Login.init();
    }

    // Export global (optionnel)
    window.Wepresta_Passwordless_Login = Wepresta_Passwordless_Login;

})();
