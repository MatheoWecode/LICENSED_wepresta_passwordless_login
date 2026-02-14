/**
 * =============================================================================
 * WEDEV UI - Point d'entrée Back-Office
 * =============================================================================
 * Import unique pour tous les utilitaires UI dans le back-office PrestaShop.
 *
 * Usage dans le module:
 * import '/path/to/Extension/UI/Assets/js/admin/index.js';
 *
 * Ou via script tag:
 * <script src="{{ asset('../modules/modulename/src/Extension/UI/Assets/js/admin/index.js') }}" type="module"></script>
 * =============================================================================
 */

// Import des composants core (Alpine)
import './wedev-core.js';

// Import des utilitaires
import { WedevAjax } from './utils/ajax.js';
import { confirm, confirmDelete, confirmSave, confirmLeave, confirmDisable, confirmIrreversible } from './utils/confirm.js';
import { notify, toast } from './utils/notifications.js';

// =============================================================================
// API Publique
// =============================================================================
const Wedev = {
    // Utilitaires AJAX
    ajax: WedevAjax,

    // Confirmation modals
    confirm,
    confirmDelete,
    confirmSave,
    confirmLeave,
    confirmDisable,
    confirmIrreversible,

    // Notifications
    notify,
    toast,

    // Version
    version: '1.0.0',

    // Méthode d'initialisation (appelée automatiquement)
    init() {
        console.log(`WEDEV UI v${this.version} initialized`);
        
        // Marquer comme initialisé
        document.documentElement.classList.add('wedev-ui-loaded');
        
        // Dispatch event pour les listeners externes
        window.dispatchEvent(new CustomEvent('wedev-ui:ready', {
            detail: { version: this.version }
        }));
    }
};

// Exposer globalement
window.Wedev = Wedev;

// Initialisation au chargement du DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Wedev.init());
} else {
    Wedev.init();
}

// Export pour usage en module
export { Wedev, WedevAjax, confirm, confirmDelete, notify, toast };
export default Wedev;





