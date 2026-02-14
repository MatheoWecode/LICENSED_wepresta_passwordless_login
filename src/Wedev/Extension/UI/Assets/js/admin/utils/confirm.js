/**
 * =============================================================================
 * WEDEV UI - Confirmation Helper
 * =============================================================================
 * Fonctions utilitaires pour les modals de confirmation.
 * Interagit avec le composant Alpine wedevConfirm.
 * =============================================================================
 */

/**
 * Affiche une modal de confirmation
 *
 * @param {Object} options - Options de la modal
 * @param {string} options.title - Titre de la modal
 * @param {string} options.message - Message de confirmation
 * @param {string} options.confirmLabel - Texte du bouton de confirmation
 * @param {string} options.cancelLabel - Texte du bouton d'annulation
 * @param {boolean} options.dangerous - Si true, le bouton de confirmation sera rouge
 * @returns {Promise<boolean>} - Résolu avec true si confirmé, false sinon
 *
 * @example
 * const confirmed = await confirm({
 *     title: 'Delete item?',
 *     message: 'This action cannot be undone.',
 *     dangerous: true
 * });
 *
 * if (confirmed) {
 *     // Supprimer l'item
 * }
 */
export function confirm(options) {
    return new Promise((resolve) => {
        const defaults = {
            title: 'Confirmation',
            message: 'Are you sure?',
            confirmLabel: 'Confirm',
            cancelLabel: 'Cancel',
            confirmClass: 'btn-primary',
            dangerous: false
        };

        const config = { ...defaults, ...options };

        if (config.dangerous) {
            config.confirmClass = 'btn-danger';
        }

        // Dispatch event pour le composant Alpine
        window.dispatchEvent(new CustomEvent('wedev-confirm', {
            detail: {
                ...config,
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false)
            }
        }));
    });
}

/**
 * Raccourci pour confirmation de suppression
 *
 * @param {string} itemName - Nom de l'élément à supprimer
 * @returns {Promise<boolean>}
 *
 * @example
 * const confirmed = await confirmDelete('Product #123');
 * if (confirmed) {
 *     await deleteProduct(123);
 * }
 */
export function confirmDelete(itemName = 'this item') {
    return confirm({
        title: 'Delete confirmation',
        message: `Are you sure you want to delete ${itemName}? This action cannot be undone.`,
        confirmLabel: 'Delete',
        cancelLabel: 'Cancel',
        dangerous: true
    });
}

/**
 * Raccourci pour confirmation de sauvegarde
 *
 * @param {string} message - Message personnalisé
 * @returns {Promise<boolean>}
 */
export function confirmSave(message = 'Do you want to save the changes?') {
    return confirm({
        title: 'Save changes',
        message: message,
        confirmLabel: 'Save',
        cancelLabel: 'Cancel'
    });
}

/**
 * Raccourci pour confirmation de navigation (perte de données)
 *
 * @returns {Promise<boolean>}
 */
export function confirmLeave() {
    return confirm({
        title: 'Unsaved changes',
        message: 'You have unsaved changes. Are you sure you want to leave this page?',
        confirmLabel: 'Leave',
        cancelLabel: 'Stay',
        dangerous: true
    });
}

/**
 * Raccourci pour confirmation de désactivation
 *
 * @param {string} itemName - Nom de l'élément
 * @returns {Promise<boolean>}
 */
export function confirmDisable(itemName = 'this item') {
    return confirm({
        title: 'Disable confirmation',
        message: `Are you sure you want to disable ${itemName}?`,
        confirmLabel: 'Disable',
        cancelLabel: 'Cancel',
        dangerous: true
    });
}

/**
 * Raccourci pour confirmation d'action irréversible
 *
 * @param {string} action - Description de l'action
 * @returns {Promise<boolean>}
 */
export function confirmIrreversible(action) {
    return confirm({
        title: 'Warning - Irreversible action',
        message: `This action (${action}) cannot be undone. Are you absolutely sure?`,
        confirmLabel: 'Yes, proceed',
        cancelLabel: 'Cancel',
        dangerous: true
    });
}

// Exposer globalement
if (typeof window !== 'undefined') {
    window.WedevConfirm = {
        confirm,
        confirmDelete,
        confirmSave,
        confirmLeave,
        confirmDisable,
        confirmIrreversible
    };
}

export default {
    confirm,
    confirmDelete,
    confirmSave,
    confirmLeave,
    confirmDisable,
    confirmIrreversible
};





