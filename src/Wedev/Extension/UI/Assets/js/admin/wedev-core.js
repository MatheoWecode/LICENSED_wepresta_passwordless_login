/**
 * =============================================================================
 * WEDEV UI Core - Alpine.js Components
 * =============================================================================
 * Initialise Alpine.js et enregistre les composants partagés.
 * Ces composants utilisent exclusivement les classes Bootstrap 4 / UIKit PS9.
 * =============================================================================
 */

// Attendre que Alpine soit disponible (chargé par PS9 ou manuellement)
document.addEventListener('alpine:init', () => {

    // =========================================================================
    // Composant: Toggle Switch
    // =========================================================================
    // Usage: <div x-data="wedevToggle(true)" @toggle-changed="handleChange">
    //            <button @click="toggle()" :class="enabled ? 'btn-success' : 'btn-secondary'">
    //                <span x-text="enabled ? 'Enabled' : 'Disabled'"></span>
    //            </button>
    //        </div>
    // =========================================================================
    Alpine.data('wedevToggle', (initialValue = false) => ({
        enabled: initialValue,

        toggle() {
            this.enabled = !this.enabled;
            this.$dispatch('toggle-changed', { value: this.enabled });
        },

        set(value) {
            this.enabled = Boolean(value);
            this.$dispatch('toggle-changed', { value: this.enabled });
        }
    }));

    // =========================================================================
    // Composant: Confirmation Modal
    // =========================================================================
    // Usage: <div x-data="wedevConfirm()" @wedev-confirm.window="show($event.detail)">
    //            <!-- Modal markup -->
    //        </div>
    //
    // Trigger: window.dispatchEvent(new CustomEvent('wedev-confirm', { detail: {...} }))
    // =========================================================================
    Alpine.data('wedevConfirm', () => ({
        open: false,
        title: '',
        message: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        confirmClass: 'btn-primary',
        onConfirm: null,
        onCancel: null,

        show(options) {
            this.title = options.title || 'Confirmation';
            this.message = options.message || 'Are you sure?';
            this.confirmLabel = options.confirmLabel || 'Confirm';
            this.cancelLabel = options.cancelLabel || 'Cancel';
            this.confirmClass = options.dangerous ? 'btn-danger' : (options.confirmClass || 'btn-primary');
            this.onConfirm = options.onConfirm;
            this.onCancel = options.onCancel;
            this.open = true;
        },

        confirm() {
            if (typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            this.open = false;
            this.reset();
        },

        cancel() {
            if (typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.open = false;
            this.reset();
        },

        reset() {
            this.title = '';
            this.message = '';
            this.onConfirm = null;
            this.onCancel = null;
        }
    }));

    // =========================================================================
    // Composant: Toast Notifications
    // =========================================================================
    // Usage: <div x-data="wedevToasts()" @wedev-toast.window="add($event.detail)">
    //            <!-- Toasts container -->
    //        </div>
    //
    // Trigger: window.dispatchEvent(new CustomEvent('wedev-toast', { 
    //              detail: { message: 'Success!', type: 'success' } 
    //          }))
    // =========================================================================
    Alpine.data('wedevToasts', () => ({
        toasts: [],
        counter: 0,

        add(detail) {
            const { message, type = 'info', duration = 5000 } = detail;
            const id = ++this.counter;
            
            this.toasts.push({ id, message, type });

            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
        },

        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },

        // Raccourcis
        success(message, duration = 5000) {
            this.add({ message, type: 'success', duration });
        },

        error(message, duration = 8000) {
            this.add({ message, type: 'danger', duration });
        },

        warning(message, duration = 6000) {
            this.add({ message, type: 'warning', duration });
        },

        info(message, duration = 5000) {
            this.add({ message, type: 'info', duration });
        }
    }));

    // =========================================================================
    // Composant: AJAX Form Handler
    // =========================================================================
    // Usage: <form x-data="wedevAjaxForm('/api/endpoint', { successMessage: 'Saved!' })"
    //              @submit.prevent="submit(new FormData($el))">
    //            <button type="submit" :disabled="loading">Save</button>
    //        </form>
    // =========================================================================
    Alpine.data('wedevAjaxForm', (url, options = {}) => ({
        loading: false,
        errors: {},
        success: false,

        async submit(formData) {
            this.loading = true;
            this.errors = {};
            this.success = false;

            try {
                const response = await fetch(url, {
                    method: options.method || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.success = true;
                    this.$dispatch('form-success', data);
                    
                    if (options.successMessage) {
                        window.dispatchEvent(new CustomEvent('wedev-toast', {
                            detail: { message: options.successMessage, type: 'success' }
                        }));
                    }

                    if (options.redirectUrl) {
                        setTimeout(() => {
                            window.location.href = data.redirectUrl || options.redirectUrl;
                        }, 500);
                    }
                } else {
                    this.errors = data.errors || {};
                    this.$dispatch('form-error', data);
                    
                    if (data.message) {
                        window.dispatchEvent(new CustomEvent('wedev-toast', {
                            detail: { message: data.message, type: 'danger' }
                        }));
                    }
                }
            } catch (error) {
                console.error('WEDEV Form Error:', error);
                this.$dispatch('form-error', { message: error.message });
                
                window.dispatchEvent(new CustomEvent('wedev-toast', {
                    detail: { message: 'An error occurred. Please try again.', type: 'danger' }
                }));
            } finally {
                this.loading = false;
            }
        },

        hasError(field) {
            return field in this.errors;
        },

        getError(field) {
            return this.errors[field] || '';
        },

        clearError(field) {
            delete this.errors[field];
        }
    }));

    // =========================================================================
    // Composant: Dropdown Menu
    // =========================================================================
    // Usage: <div x-data="wedevDropdown()">
    //            <button @click="toggle()">Menu</button>
    //            <div x-show="open" @click.outside="close()">...</div>
    //        </div>
    // =========================================================================
    Alpine.data('wedevDropdown', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        }
    }));

    // =========================================================================
    // Composant: Sortable List
    // =========================================================================
    // Usage: <ul x-data="wedevSortable('/api/reorder')" @sort-end="handleSort">
    //            <li data-id="1">Item 1</li>
    //        </ul>
    // =========================================================================
    Alpine.data('wedevSortable', (updateUrl) => ({
        items: [],
        draggedItem: null,

        init() {
            // Récupérer les items depuis le DOM
            this.items = Array.from(this.$el.querySelectorAll('[data-id]')).map(el => ({
                id: el.dataset.id,
                element: el
            }));
        },

        async updatePositions(orderedIds) {
            if (!updateUrl) return;

            try {
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ positions: orderedIds })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.$dispatch('sort-success', data);
                } else {
                    this.$dispatch('sort-error', data);
                }
            } catch (error) {
                console.error('WEDEV Sortable Error:', error);
                this.$dispatch('sort-error', { message: error.message });
            }
        }
    }));

    // =========================================================================
    // Composant: Character Counter
    // =========================================================================
    // Usage: <div x-data="wedevCharCounter(500)">
    //            <textarea x-model="text" :maxlength="max"></textarea>
    //            <span x-text="remaining"></span>
    //        </div>
    // =========================================================================
    Alpine.data('wedevCharCounter', (maxLength = 500) => ({
        text: '',
        max: maxLength,

        get count() {
            return this.text.length;
        },

        get remaining() {
            return this.max - this.text.length;
        },

        get isNearLimit() {
            return this.remaining <= this.max * 0.1; // 10% restant
        },

        get isOverLimit() {
            return this.remaining < 0;
        }
    }));

    // =========================================================================
    // Composant: Copy to Clipboard
    // =========================================================================
    // Usage: <button x-data="wedevClipboard()" @click="copy('text to copy')">
    //            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
    //        </button>
    // =========================================================================
    Alpine.data('wedevClipboard', () => ({
        copied: false,

        async copy(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                
                setTimeout(() => {
                    this.copied = false;
                }, 2000);
                
                this.$dispatch('copied', { text });
            } catch (error) {
                console.error('WEDEV Clipboard Error:', error);
            }
        }
    }));

});

// =============================================================================
// Auto-init Alpine si pas déjà chargé
// =============================================================================
if (typeof window.Alpine === 'undefined') {
    console.log('WEDEV UI: Loading Alpine.js...');
    
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
    script.defer = true;
    document.head.appendChild(script);
}

// =============================================================================
// Marqueur d'initialisation
// =============================================================================
window.WedevUIInitialized = true;
console.log('WEDEV UI Core loaded');





