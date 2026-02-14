/**
 * =============================================================================
 * WEDEV UI - Notifications Helper
 * =============================================================================
 * Fonctions utilitaires pour les notifications toast.
 * Interagit avec le composant Alpine wedevToasts.
 * =============================================================================
 */

/**
 * Affiche une notification toast
 *
 * @param {string} message - Message à afficher
 * @param {string} type - Type: 'success', 'info', 'warning', 'danger'
 * @param {number} duration - Durée d'affichage en ms (0 = permanent)
 *
 * @example
 * notify.show('Item saved successfully!', 'success');
 * notify.show('Please check your input', 'warning', 8000);
 */
export const notify = {
    /**
     * Affiche une notification personnalisée
     */
    show(message, type = 'info', duration = 5000) {
        window.dispatchEvent(new CustomEvent('wedev-toast', {
            detail: { message, type, duration }
        }));
    },

    /**
     * Notification de succès
     * @param {string} message
     * @param {number} duration
     */
    success(message, duration = 5000) {
        this.show(message, 'success', duration);
    },

    /**
     * Notification d'erreur
     * @param {string} message
     * @param {number} duration - Plus long par défaut pour les erreurs
     */
    error(message, duration = 8000) {
        this.show(message, 'danger', duration);
    },

    /**
     * Notification d'avertissement
     * @param {string} message
     * @param {number} duration
     */
    warning(message, duration = 6000) {
        this.show(message, 'warning', duration);
    },

    /**
     * Notification d'information
     * @param {string} message
     * @param {number} duration
     */
    info(message, duration = 5000) {
        this.show(message, 'info', duration);
    },

    /**
     * Notification persistante (ne disparaît pas automatiquement)
     * @param {string} message
     * @param {string} type
     */
    persistent(message, type = 'info') {
        this.show(message, type, 0);
    },

    /**
     * Notification de chargement (info persistante)
     * @param {string} message
     * @returns {Function} Fonction pour fermer la notification
     */
    loading(message = 'Loading...') {
        const id = Date.now();
        
        window.dispatchEvent(new CustomEvent('wedev-toast', {
            detail: { 
                id,
                message, 
                type: 'info', 
                duration: 0,
                isLoading: true
            }
        }));

        // Retourne une fonction pour fermer la notification
        return () => {
            window.dispatchEvent(new CustomEvent('wedev-toast-remove', {
                detail: { id }
            }));
        };
    },

    /**
     * Affiche le résultat d'une promesse
     * @param {Promise} promise
     * @param {Object} messages
     * @param {string} messages.loading - Message pendant le chargement
     * @param {string} messages.success - Message en cas de succès
     * @param {string} messages.error - Message en cas d'erreur
     * @returns {Promise}
     */
    async promise(promise, messages = {}) {
        const {
            loading = 'Loading...',
            success = 'Success!',
            error = 'An error occurred'
        } = messages;

        const closeLoading = this.loading(loading);

        try {
            const result = await promise;
            closeLoading();
            this.success(success);
            return result;
        } catch (err) {
            closeLoading();
            this.error(typeof error === 'function' ? error(err) : error);
            throw err;
        }
    }
};

// Alias pour compatibilité
export const toast = notify;

// Exposer globalement
if (typeof window !== 'undefined') {
    window.WedevNotify = notify;
    window.WedevToast = notify; // Alias
}

export default notify;





