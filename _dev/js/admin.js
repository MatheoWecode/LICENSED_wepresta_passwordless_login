/**
 * Module Starter - Back-office JavaScript
 *
 * Fonctionnalités admin avec jQuery (requis par PrestaShop BO)
 */

import '../scss/admin.scss';

(function ($) {
    'use strict';

    /**
     * Admin Module Starter
     */
    const Wepresta_Passwordless_LoginAdmin = {
        /**
         * Initialisation
         */
        init: function () {
            this.bindEvents();
            this.initSortable();
            this.initTooltips();

            console.debug('[Wepresta_Passwordless_LoginAdmin] Initialized');
        },

        /**
         * Bind des événements
         */
        bindEvents: function () {
            // Toggle switch
            $(document).on('change', '.wepresta_passwordless_login-toggle', this.handleToggle.bind(this));

            // Actions en masse
            $(document).on('click', '.js-wepresta_passwordless_login-bulk-action', this.handleBulkAction.bind(this));

            // Confirmation suppression
            $(document).on('click', '.js-wepresta_passwordless_login-delete', this.handleDelete.bind(this));

            // Form validation
            $(document).on('submit', '.wepresta_passwordless_login-form', this.handleFormSubmit.bind(this));

            // Image preview
            $(document).on('change', '.wepresta_passwordless_login-image-input', this.handleImagePreview.bind(this));

            // AJAX form
            $(document).on('submit', '.wepresta_passwordless_login-ajax-form', this.handleAjaxForm.bind(this));
        },

        /**
         * Initialise le tri drag & drop
         */
        initSortable: function () {
            const $sortable = $('.wepresta_passwordless_login-sortable');

            if ($sortable.length && $.fn.sortable) {
                $sortable.sortable({
                    handle: '.sortable-handle',
                    placeholder: 'sortable-placeholder',
                    update: this.handleSortUpdate.bind(this),
                });
            }
        },

        /**
         * Initialise les tooltips Bootstrap
         */
        initTooltips: function () {
            $('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip();
        },

        /**
         * Gestionnaire toggle actif/inactif
         */
        handleToggle: function (event) {
            const $toggle = $(event.currentTarget);
            const itemId = $toggle.data('id');
            const newState = $toggle.is(':checked');

            this.showLoader($toggle.closest('tr'));

            $.ajax({
                url: $toggle.data('url') || window.moduleStarterAdminUrl,
                method: 'POST',
                data: {
                    action: 'toggle',
                    id: itemId,
                    active: newState ? 1 : 0,
                    ajax: 1,
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.message || 'Status updated');
                    } else {
                        $toggle.prop('checked', !newState);
                        this.showError(response.message || 'Error updating status');
                    }
                },
                error: () => {
                    $toggle.prop('checked', !newState);
                    this.showError('Network error');
                },
                complete: () => {
                    this.hideLoader($toggle.closest('tr'));
                },
            });
        },

        /**
         * Gestionnaire action en masse
         */
        handleBulkAction: function (event) {
            event.preventDefault();

            const $button = $(event.currentTarget);
            const action = $button.data('action');
            const $checked = $('input.bulk-checkbox:checked');

            if ($checked.length === 0) {
                this.showWarning('Please select at least one item');
                return;
            }

            const ids = $checked.map(function () {
                return $(this).val();
            }).get();

            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete ' + ids.length + ' item(s)?')) {
                    return;
                }
            }

            this.showLoader();

            $.ajax({
                url: window.moduleStarterAdminUrl,
                method: 'POST',
                data: {
                    action: 'bulk_' + action,
                    ids: ids,
                    ajax: 1,
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.message);
                        location.reload();
                    } else {
                        this.showError(response.message);
                    }
                },
                error: () => {
                    this.showError('Bulk action failed');
                },
                complete: () => {
                    this.hideLoader();
                },
            });
        },

        /**
         * Gestionnaire suppression
         */
        handleDelete: function (event) {
            event.preventDefault();

            const $button = $(event.currentTarget);

            if (!confirm($button.data('confirm') || 'Are you sure you want to delete this item?')) {
                return;
            }

            const $row = $button.closest('tr');
            this.showLoader($row);

            $.ajax({
                url: $button.attr('href') || $button.data('url'),
                method: 'POST',
                data: {
                    action: 'delete',
                    id: $button.data('id'),
                    ajax: 1,
                },
                success: (response) => {
                    if (response.success) {
                        $row.fadeOut(300, function () {
                            $(this).remove();
                        });
                        this.showSuccess(response.message || 'Item deleted');
                    } else {
                        this.showError(response.message || 'Delete failed');
                    }
                },
                error: () => {
                    this.showError('Delete failed');
                },
                complete: () => {
                    this.hideLoader($row);
                },
            });
        },

        /**
         * Gestionnaire mise à jour ordre
         */
        handleSortUpdate: function (event, ui) {
            const $items = $(event.target).find('.sortable-item');
            const positions = [];

            $items.each(function (index) {
                positions.push({
                    id: $(this).data('id'),
                    position: index,
                });
            });

            $.ajax({
                url: window.moduleStarterAdminUrl,
                method: 'POST',
                data: {
                    action: 'updatePositions',
                    positions: positions,
                    ajax: 1,
                },
                success: (response) => {
                    if (!response.success) {
                        this.showError('Position update failed');
                    }
                },
                error: () => {
                    this.showError('Position update failed');
                },
            });
        },

        /**
         * Gestionnaire soumission formulaire
         */
        handleFormSubmit: function (event) {
            const $form = $(event.currentTarget);

            // Validation côté client
            let isValid = true;

            $form.find('[required]').each(function () {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
                this.showError('Please fill in all required fields');
            }
        },

        /**
         * Gestionnaire formulaire AJAX
         */
        handleAjaxForm: function (event) {
            event.preventDefault();

            const $form = $(event.currentTarget);
            const $submit = $form.find('[type="submit"]');

            $submit.prop('disabled', true).addClass('loading');

            $.ajax({
                url: $form.attr('action'),
                method: $form.attr('method') || 'POST',
                data: $form.serialize(),
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.message);

                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    } else {
                        this.showError(response.message || 'Form submission failed');

                        if (response.errors) {
                            this.displayFormErrors($form, response.errors);
                        }
                    }
                },
                error: () => {
                    this.showError('Form submission failed');
                },
                complete: () => {
                    $submit.prop('disabled', false).removeClass('loading');
                },
            });
        },

        /**
         * Gestionnaire preview image
         */
        handleImagePreview: function (event) {
            const $input = $(event.currentTarget);
            const $preview = $input.siblings('.image-preview');

            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    $preview.attr('src', e.target.result).show();
                };

                reader.readAsDataURL(event.target.files[0]);
            }
        },

        /**
         * Affiche les erreurs de formulaire
         */
        displayFormErrors: function ($form, errors) {
            // Clear previous errors
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();

            // Display new errors
            Object.keys(errors).forEach((field) => {
                const $field = $form.find('[name="' + field + '"]');
                $field.addClass('is-invalid');
                $field.after('<div class="invalid-feedback">' + errors[field] + '</div>');
            });
        },

        /**
         * Affiche un loader
         */
        showLoader: function ($element) {
            if ($element) {
                $element.addClass('loading');
            } else {
                $('body').addClass('loading-overlay');
            }
        },

        /**
         * Cache le loader
         */
        hideLoader: function ($element) {
            if ($element) {
                $element.removeClass('loading');
            } else {
                $('body').removeClass('loading-overlay');
            }
        },

        /**
         * Affiche un message de succès
         */
        showSuccess: function (message) {
            if (typeof showSuccessMessage === 'function') {
                showSuccessMessage(message);
            } else {
                alert(message);
            }
        },

        /**
         * Affiche un message d'erreur
         */
        showError: function (message) {
            if (typeof showErrorMessage === 'function') {
                showErrorMessage(message);
            } else {
                alert('Error: ' + message);
            }
        },

        /**
         * Affiche un avertissement
         */
        showWarning: function (message) {
            alert(message);
        },
    };

    // Initialisation au chargement
    $(document).ready(function () {
        Wepresta_Passwordless_LoginAdmin.init();
    });

    // Export global
    window.Wepresta_Passwordless_LoginAdmin = Wepresta_Passwordless_LoginAdmin;
})(window.jQuery || window.$);

