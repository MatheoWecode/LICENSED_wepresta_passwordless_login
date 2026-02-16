/*!
 * Module for PrestaShop
 *
 *                 xxxx
 *                 xxxx
 *
 *                 @author      WePresta
 *                 @copyright   2026 WePresta
 *                 @license     MIT
 */
"use strict";
(self["webpackChunkwepresta_passwordless_login"] = self["webpackChunkwepresta_passwordless_login"] || []).push([["admin"],{

/***/ "./_dev/js/admin.js":
/*!**************************!*\
  !*** ./_dev/js/admin.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_es_array_push_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.push.js */ "./node_modules/core-js/modules/es.array.push.js");
/* harmony import */ var core_js_modules_es_array_push_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_push_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/esnext.iterator.find.js */ "./node_modules/core-js/modules/esnext.iterator.find.js");
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/esnext.iterator.for-each.js */ "./node_modules/core-js/modules/esnext.iterator.for-each.js");
/* harmony import */ var core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_for_each_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/esnext.iterator.map.js */ "./node_modules/core-js/modules/esnext.iterator.map.js");
/* harmony import */ var core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_map_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _scss_admin_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../scss/admin.scss */ "./_dev/scss/admin.scss");





/**
 * Module Starter - Back-office JavaScript
 *
 * Fonctionnalités admin avec jQuery (requis par PrestaShop BO)
 */


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
          update: this.handleSortUpdate.bind(this)
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
          ajax: 1
        },
        success: response => {
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
        }
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
          ajax: 1
        },
        success: response => {
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
        }
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
          ajax: 1
        },
        success: response => {
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
        }
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
          position: index
        });
      });
      $.ajax({
        url: window.moduleStarterAdminUrl,
        method: 'POST',
        data: {
          action: 'updatePositions',
          positions: positions,
          ajax: 1
        },
        success: response => {
          if (!response.success) {
            this.showError('Position update failed');
          }
        },
        error: () => {
          this.showError('Position update failed');
        }
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
        success: response => {
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
        }
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
      Object.keys(errors).forEach(field => {
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
    }
  };

  // Initialisation au chargement
  $(document).ready(function () {
    Wepresta_Passwordless_LoginAdmin.init();
  });

  // Export global
  window.Wepresta_Passwordless_LoginAdmin = Wepresta_Passwordless_LoginAdmin;
})(window.jQuery || window.$);

/***/ }),

/***/ "./_dev/scss/admin.scss":
/*!******************************!*\
  !*** ./_dev/scss/admin.scss ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ var __webpack_exec__ = function(moduleId) { return __webpack_require__(__webpack_require__.s = moduleId); }
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_modules_esnext_iterator_constructor_js-node_modules_core-js_modu-bd769a","vendors-node_modules_core-js_modules_es_array_push_js-node_modules_core-js_modules_esnext_ite-98dab4"], function() { return __webpack_exec__("./_dev/js/admin.js"); });
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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LFNBQVMsRUFBRSxTQUFBQSxDQUFVRixPQUFPLEVBQUU7TUFDMUIsSUFBSSxPQUFPNEQsZ0JBQWdCLEtBQUssVUFBVSxFQUFFO1FBQ3hDQSxnQkFBZ0IsQ0FBQzVELE9BQU8sQ0FBQztNQUM3QixDQUFDLE1BQU07UUFDSDJELEtBQUssQ0FBQyxTQUFTLEdBQUczRCxPQUFPLENBQUM7TUFDOUI7SUFDSixDQUFDO0lBRUQ7QUFDUjtBQUNBO0lBQ1FTLFdBQVcsRUFBRSxTQUFBQSxDQUFVVCxPQUFPLEVBQUU7TUFDNUIyRCxLQUFLLENBQUMzRCxPQUFPLENBQUM7SUFDbEI7RUFDSixDQUFDOztFQUVEO0VBQ0E5QyxDQUFDLENBQUNRLFFBQVEsQ0FBQyxDQUFDbUcsS0FBSyxDQUFDLFlBQVk7SUFDMUIxRyxnQ0FBZ0MsQ0FBQ0MsSUFBSSxDQUFDLENBQUM7RUFDM0MsQ0FBQyxDQUFDOztFQUVGO0VBQ0FtQyxNQUFNLENBQUNwQyxnQ0FBZ0MsR0FBR0EsZ0NBQWdDO0FBQzlFLENBQUMsRUFBRW9DLE1BQU0sQ0FBQ3VFLE1BQU0sSUFBSXZFLE1BQU0sQ0FBQ3JDLENBQUMsQ0FBQyxDOzs7Ozs7Ozs7OztBQ3ZZN0IiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly93ZXByZXN0YV9wYXNzd29yZGxlc3NfbG9naW4vLi9fZGV2L2pzL2FkbWluLmpzIiwid2VicGFjazovL3dlcHJlc3RhX3Bhc3N3b3JkbGVzc19sb2dpbi8uL19kZXYvc2Nzcy9hZG1pbi5zY3NzPzI5MWEiXSwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBNb2R1bGUgU3RhcnRlciAtIEJhY2stb2ZmaWNlIEphdmFTY3JpcHRcbiAqXG4gKiBGb25jdGlvbm5hbGl0w6lzIGFkbWluIGF2ZWMgalF1ZXJ5IChyZXF1aXMgcGFyIFByZXN0YVNob3AgQk8pXG4gKi9cblxuaW1wb3J0ICcuLi9zY3NzL2FkbWluLnNjc3MnO1xuXG4oZnVuY3Rpb24gKCQpIHtcbiAgICAndXNlIHN0cmljdCc7XG5cbiAgICAvKipcbiAgICAgKiBBZG1pbiBNb2R1bGUgU3RhcnRlclxuICAgICAqL1xuICAgIGNvbnN0IFdlcHJlc3RhX1Bhc3N3b3JkbGVzc19Mb2dpbkFkbWluID0ge1xuICAgICAgICAvKipcbiAgICAgICAgICogSW5pdGlhbGlzYXRpb25cbiAgICAgICAgICovXG4gICAgICAgIGluaXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHRoaXMuYmluZEV2ZW50cygpO1xuICAgICAgICAgICAgdGhpcy5pbml0U29ydGFibGUoKTtcbiAgICAgICAgICAgIHRoaXMuaW5pdFRvb2x0aXBzKCk7XG5cbiAgICAgICAgICAgIGNvbnNvbGUuZGVidWcoJ1tXZXByZXN0YV9QYXNzd29yZGxlc3NfTG9naW5BZG1pbl0gSW5pdGlhbGl6ZWQnKTtcbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogQmluZCBkZXMgw6l2w6luZW1lbnRzXG4gICAgICAgICAqL1xuICAgICAgICBiaW5kRXZlbnRzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAvLyBUb2dnbGUgc3dpdGNoXG4gICAgICAgICAgICAkKGRvY3VtZW50KS5vbignY2hhbmdlJywgJy53ZXByZXN0YV9wYXNzd29yZGxlc3NfbG9naW4tdG9nZ2xlJywgdGhpcy5oYW5kbGVUb2dnbGUuYmluZCh0aGlzKSk7XG5cbiAgICAgICAgICAgIC8vIEFjdGlvbnMgZW4gbWFzc2VcbiAgICAgICAgICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcuanMtd2VwcmVzdGFfcGFzc3dvcmRsZXNzX2xvZ2luLWJ1bGstYWN0aW9uJywgdGhpcy5oYW5kbGVCdWxrQWN0aW9uLmJpbmQodGhpcykpO1xuXG4gICAgICAgICAgICAvLyBDb25maXJtYXRpb24gc3VwcHJlc3Npb25cbiAgICAgICAgICAgICQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcuanMtd2VwcmVzdGFfcGFzc3dvcmRsZXNzX2xvZ2luLWRlbGV0ZScsIHRoaXMuaGFuZGxlRGVsZXRlLmJpbmQodGhpcykpO1xuXG4gICAgICAgICAgICAvLyBGb3JtIHZhbGlkYXRpb25cbiAgICAgICAgICAgICQoZG9jdW1lbnQpLm9uKCdzdWJtaXQnLCAnLndlcHJlc3RhX3Bhc3N3b3JkbGVzc19sb2dpbi1mb3JtJywgdGhpcy5oYW5kbGVGb3JtU3VibWl0LmJpbmQodGhpcykpO1xuXG4gICAgICAgICAgICAvLyBJbWFnZSBwcmV2aWV3XG4gICAgICAgICAgICAkKGRvY3VtZW50KS5vbignY2hhbmdlJywgJy53ZXByZXN0YV9wYXNzd29yZGxlc3NfbG9naW4taW1hZ2UtaW5wdXQnLCB0aGlzLmhhbmRsZUltYWdlUHJldmlldy5iaW5kKHRoaXMpKTtcblxuICAgICAgICAgICAgLy8gQUpBWCBmb3JtXG4gICAgICAgICAgICAkKGRvY3VtZW50KS5vbignc3VibWl0JywgJy53ZXByZXN0YV9wYXNzd29yZGxlc3NfbG9naW4tYWpheC1mb3JtJywgdGhpcy5oYW5kbGVBamF4Rm9ybS5iaW5kKHRoaXMpKTtcbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogSW5pdGlhbGlzZSBsZSB0cmkgZHJhZyAmIGRyb3BcbiAgICAgICAgICovXG4gICAgICAgIGluaXRTb3J0YWJsZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgY29uc3QgJHNvcnRhYmxlID0gJCgnLndlcHJlc3RhX3Bhc3N3b3JkbGVzc19sb2dpbi1zb3J0YWJsZScpO1xuXG4gICAgICAgICAgICBpZiAoJHNvcnRhYmxlLmxlbmd0aCAmJiAkLmZuLnNvcnRhYmxlKSB7XG4gICAgICAgICAgICAgICAgJHNvcnRhYmxlLnNvcnRhYmxlKHtcbiAgICAgICAgICAgICAgICAgICAgaGFuZGxlOiAnLnNvcnRhYmxlLWhhbmRsZScsXG4gICAgICAgICAgICAgICAgICAgIHBsYWNlaG9sZGVyOiAnc29ydGFibGUtcGxhY2Vob2xkZXInLFxuICAgICAgICAgICAgICAgICAgICB1cGRhdGU6IHRoaXMuaGFuZGxlU29ydFVwZGF0ZS5iaW5kKHRoaXMpLFxuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBJbml0aWFsaXNlIGxlcyB0b29sdGlwcyBCb290c3RyYXBcbiAgICAgICAgICovXG4gICAgICAgIGluaXRUb29sdGlwczogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgJCgnW2RhdGEtdG9nZ2xlPVwidG9vbHRpcFwiXSwgW2RhdGEtYnMtdG9nZ2xlPVwidG9vbHRpcFwiXScpLnRvb2x0aXAoKTtcbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogR2VzdGlvbm5haXJlIHRvZ2dsZSBhY3RpZi9pbmFjdGlmXG4gICAgICAgICAqL1xuICAgICAgICBoYW5kbGVUb2dnbGU6IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgY29uc3QgJHRvZ2dsZSA9ICQoZXZlbnQuY3VycmVudFRhcmdldCk7XG4gICAgICAgICAgICBjb25zdCBpdGVtSWQgPSAkdG9nZ2xlLmRhdGEoJ2lkJyk7XG4gICAgICAgICAgICBjb25zdCBuZXdTdGF0ZSA9ICR0b2dnbGUuaXMoJzpjaGVja2VkJyk7XG5cbiAgICAgICAgICAgIHRoaXMuc2hvd0xvYWRlcigkdG9nZ2xlLmNsb3Nlc3QoJ3RyJykpO1xuXG4gICAgICAgICAgICAkLmFqYXgoe1xuICAgICAgICAgICAgICAgIHVybDogJHRvZ2dsZS5kYXRhKCd1cmwnKSB8fCB3aW5kb3cubW9kdWxlU3RhcnRlckFkbWluVXJsLFxuICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgICAgIGRhdGE6IHtcbiAgICAgICAgICAgICAgICAgICAgYWN0aW9uOiAndG9nZ2xlJyxcbiAgICAgICAgICAgICAgICAgICAgaWQ6IGl0ZW1JZCxcbiAgICAgICAgICAgICAgICAgICAgYWN0aXZlOiBuZXdTdGF0ZSA/IDEgOiAwLFxuICAgICAgICAgICAgICAgICAgICBhamF4OiAxLFxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgc3VjY2VzczogKHJlc3BvbnNlKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5zdWNjZXNzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnNob3dTdWNjZXNzKHJlc3BvbnNlLm1lc3NhZ2UgfHwgJ1N0YXR1cyB1cGRhdGVkJyk7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkdG9nZ2xlLnByb3AoJ2NoZWNrZWQnLCAhbmV3U3RhdGUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IocmVzcG9uc2UubWVzc2FnZSB8fCAnRXJyb3IgdXBkYXRpbmcgc3RhdHVzJyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGVycm9yOiAoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICR0b2dnbGUucHJvcCgnY2hlY2tlZCcsICFuZXdTdGF0ZSk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2hvd0Vycm9yKCdOZXR3b3JrIGVycm9yJyk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBjb21wbGV0ZTogKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmhpZGVMb2FkZXIoJHRvZ2dsZS5jbG9zZXN0KCd0cicpKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIEdlc3Rpb25uYWlyZSBhY3Rpb24gZW4gbWFzc2VcbiAgICAgICAgICovXG4gICAgICAgIGhhbmRsZUJ1bGtBY3Rpb246IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgICAgICAgY29uc3QgJGJ1dHRvbiA9ICQoZXZlbnQuY3VycmVudFRhcmdldCk7XG4gICAgICAgICAgICBjb25zdCBhY3Rpb24gPSAkYnV0dG9uLmRhdGEoJ2FjdGlvbicpO1xuICAgICAgICAgICAgY29uc3QgJGNoZWNrZWQgPSAkKCdpbnB1dC5idWxrLWNoZWNrYm94OmNoZWNrZWQnKTtcblxuICAgICAgICAgICAgaWYgKCRjaGVja2VkLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgICAgIHRoaXMuc2hvd1dhcm5pbmcoJ1BsZWFzZSBzZWxlY3QgYXQgbGVhc3Qgb25lIGl0ZW0nKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGNvbnN0IGlkcyA9ICRjaGVja2VkLm1hcChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuICQodGhpcykudmFsKCk7XG4gICAgICAgICAgICB9KS5nZXQoKTtcblxuICAgICAgICAgICAgaWYgKGFjdGlvbiA9PT0gJ2RlbGV0ZScpIHtcbiAgICAgICAgICAgICAgICBpZiAoIWNvbmZpcm0oJ0FyZSB5b3Ugc3VyZSB5b3Ugd2FudCB0byBkZWxldGUgJyArIGlkcy5sZW5ndGggKyAnIGl0ZW0ocyk/JykpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdGhpcy5zaG93TG9hZGVyKCk7XG5cbiAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgdXJsOiB3aW5kb3cubW9kdWxlU3RhcnRlckFkbWluVXJsLFxuICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgICAgIGRhdGE6IHtcbiAgICAgICAgICAgICAgICAgICAgYWN0aW9uOiAnYnVsa18nICsgYWN0aW9uLFxuICAgICAgICAgICAgICAgICAgICBpZHM6IGlkcyxcbiAgICAgICAgICAgICAgICAgICAgYWpheDogMSxcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHN1Y2Nlc3M6IChyZXNwb25zZSkgPT4ge1xuICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2Uuc3VjY2Vzcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93U3VjY2VzcyhyZXNwb25zZS5tZXNzYWdlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGxvY2F0aW9uLnJlbG9hZCgpO1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IocmVzcG9uc2UubWVzc2FnZSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGVycm9yOiAoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2hvd0Vycm9yKCdCdWxrIGFjdGlvbiBmYWlsZWQnKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGNvbXBsZXRlOiAoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuaGlkZUxvYWRlcigpO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogR2VzdGlvbm5haXJlIHN1cHByZXNzaW9uXG4gICAgICAgICAqL1xuICAgICAgICBoYW5kbGVEZWxldGU6IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgICAgICAgY29uc3QgJGJ1dHRvbiA9ICQoZXZlbnQuY3VycmVudFRhcmdldCk7XG5cbiAgICAgICAgICAgIGlmICghY29uZmlybSgkYnV0dG9uLmRhdGEoJ2NvbmZpcm0nKSB8fCAnQXJlIHlvdSBzdXJlIHlvdSB3YW50IHRvIGRlbGV0ZSB0aGlzIGl0ZW0/JykpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGNvbnN0ICRyb3cgPSAkYnV0dG9uLmNsb3Nlc3QoJ3RyJyk7XG4gICAgICAgICAgICB0aGlzLnNob3dMb2FkZXIoJHJvdyk7XG5cbiAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgdXJsOiAkYnV0dG9uLmF0dHIoJ2hyZWYnKSB8fCAkYnV0dG9uLmRhdGEoJ3VybCcpLFxuICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgICAgIGRhdGE6IHtcbiAgICAgICAgICAgICAgICAgICAgYWN0aW9uOiAnZGVsZXRlJyxcbiAgICAgICAgICAgICAgICAgICAgaWQ6ICRidXR0b24uZGF0YSgnaWQnKSxcbiAgICAgICAgICAgICAgICAgICAgYWpheDogMSxcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHN1Y2Nlc3M6IChyZXNwb25zZSkgPT4ge1xuICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2Uuc3VjY2Vzcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgJHJvdy5mYWRlT3V0KDMwMCwgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICQodGhpcykucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuc2hvd1N1Y2Nlc3MocmVzcG9uc2UubWVzc2FnZSB8fCAnSXRlbSBkZWxldGVkJyk7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnNob3dFcnJvcihyZXNwb25zZS5tZXNzYWdlIHx8ICdEZWxldGUgZmFpbGVkJyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGVycm9yOiAoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2hvd0Vycm9yKCdEZWxldGUgZmFpbGVkJyk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBjb21wbGV0ZTogKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmhpZGVMb2FkZXIoJHJvdyk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBHZXN0aW9ubmFpcmUgbWlzZSDDoCBqb3VyIG9yZHJlXG4gICAgICAgICAqL1xuICAgICAgICBoYW5kbGVTb3J0VXBkYXRlOiBmdW5jdGlvbiAoZXZlbnQsIHVpKSB7XG4gICAgICAgICAgICBjb25zdCAkaXRlbXMgPSAkKGV2ZW50LnRhcmdldCkuZmluZCgnLnNvcnRhYmxlLWl0ZW0nKTtcbiAgICAgICAgICAgIGNvbnN0IHBvc2l0aW9ucyA9IFtdO1xuXG4gICAgICAgICAgICAkaXRlbXMuZWFjaChmdW5jdGlvbiAoaW5kZXgpIHtcbiAgICAgICAgICAgICAgICBwb3NpdGlvbnMucHVzaCh7XG4gICAgICAgICAgICAgICAgICAgIGlkOiAkKHRoaXMpLmRhdGEoJ2lkJyksXG4gICAgICAgICAgICAgICAgICAgIHBvc2l0aW9uOiBpbmRleCxcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAkLmFqYXgoe1xuICAgICAgICAgICAgICAgIHVybDogd2luZG93Lm1vZHVsZVN0YXJ0ZXJBZG1pblVybCxcbiAgICAgICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgICAgICBkYXRhOiB7XG4gICAgICAgICAgICAgICAgICAgIGFjdGlvbjogJ3VwZGF0ZVBvc2l0aW9ucycsXG4gICAgICAgICAgICAgICAgICAgIHBvc2l0aW9uczogcG9zaXRpb25zLFxuICAgICAgICAgICAgICAgICAgICBhamF4OiAxLFxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgc3VjY2VzczogKHJlc3BvbnNlKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGlmICghcmVzcG9uc2Uuc3VjY2Vzcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IoJ1Bvc2l0aW9uIHVwZGF0ZSBmYWlsZWQnKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgZXJyb3I6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IoJ1Bvc2l0aW9uIHVwZGF0ZSBmYWlsZWQnKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIEdlc3Rpb25uYWlyZSBzb3VtaXNzaW9uIGZvcm11bGFpcmVcbiAgICAgICAgICovXG4gICAgICAgIGhhbmRsZUZvcm1TdWJtaXQ6IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgY29uc3QgJGZvcm0gPSAkKGV2ZW50LmN1cnJlbnRUYXJnZXQpO1xuXG4gICAgICAgICAgICAvLyBWYWxpZGF0aW9uIGPDtHTDqSBjbGllbnRcbiAgICAgICAgICAgIGxldCBpc1ZhbGlkID0gdHJ1ZTtcblxuICAgICAgICAgICAgJGZvcm0uZmluZCgnW3JlcXVpcmVkXScpLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIGlmICghJCh0aGlzKS52YWwoKSkge1xuICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLmFkZENsYXNzKCdpcy1pbnZhbGlkJyk7XG4gICAgICAgICAgICAgICAgICAgIGlzVmFsaWQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAkKHRoaXMpLnJlbW92ZUNsYXNzKCdpcy1pbnZhbGlkJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIGlmICghaXNWYWxpZCkge1xuICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IoJ1BsZWFzZSBmaWxsIGluIGFsbCByZXF1aXJlZCBmaWVsZHMnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogR2VzdGlvbm5haXJlIGZvcm11bGFpcmUgQUpBWFxuICAgICAgICAgKi9cbiAgICAgICAgaGFuZGxlQWpheEZvcm06IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgICAgICAgY29uc3QgJGZvcm0gPSAkKGV2ZW50LmN1cnJlbnRUYXJnZXQpO1xuICAgICAgICAgICAgY29uc3QgJHN1Ym1pdCA9ICRmb3JtLmZpbmQoJ1t0eXBlPVwic3VibWl0XCJdJyk7XG5cbiAgICAgICAgICAgICRzdWJtaXQucHJvcCgnZGlzYWJsZWQnLCB0cnVlKS5hZGRDbGFzcygnbG9hZGluZycpO1xuXG4gICAgICAgICAgICAkLmFqYXgoe1xuICAgICAgICAgICAgICAgIHVybDogJGZvcm0uYXR0cignYWN0aW9uJyksXG4gICAgICAgICAgICAgICAgbWV0aG9kOiAkZm9ybS5hdHRyKCdtZXRob2QnKSB8fCAnUE9TVCcsXG4gICAgICAgICAgICAgICAgZGF0YTogJGZvcm0uc2VyaWFsaXplKCksXG4gICAgICAgICAgICAgICAgc3VjY2VzczogKHJlc3BvbnNlKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5zdWNjZXNzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnNob3dTdWNjZXNzKHJlc3BvbnNlLm1lc3NhZ2UpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2UucmVkaXJlY3QpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IHJlc3BvbnNlLnJlZGlyZWN0O1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IocmVzcG9uc2UubWVzc2FnZSB8fCAnRm9ybSBzdWJtaXNzaW9uIGZhaWxlZCcpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2UuZXJyb3JzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5kaXNwbGF5Rm9ybUVycm9ycygkZm9ybSwgcmVzcG9uc2UuZXJyb3JzKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgZXJyb3I6ICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5zaG93RXJyb3IoJ0Zvcm0gc3VibWlzc2lvbiBmYWlsZWQnKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGNvbXBsZXRlOiAoKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICRzdWJtaXQucHJvcCgnZGlzYWJsZWQnLCBmYWxzZSkucmVtb3ZlQ2xhc3MoJ2xvYWRpbmcnKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIEdlc3Rpb25uYWlyZSBwcmV2aWV3IGltYWdlXG4gICAgICAgICAqL1xuICAgICAgICBoYW5kbGVJbWFnZVByZXZpZXc6IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICAgICAgY29uc3QgJGlucHV0ID0gJChldmVudC5jdXJyZW50VGFyZ2V0KTtcbiAgICAgICAgICAgIGNvbnN0ICRwcmV2aWV3ID0gJGlucHV0LnNpYmxpbmdzKCcuaW1hZ2UtcHJldmlldycpO1xuXG4gICAgICAgICAgICBpZiAoZXZlbnQudGFyZ2V0LmZpbGVzICYmIGV2ZW50LnRhcmdldC5maWxlc1swXSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IHJlYWRlciA9IG5ldyBGaWxlUmVhZGVyKCk7XG5cbiAgICAgICAgICAgICAgICByZWFkZXIub25sb2FkID0gZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAgICAgICAgICAgJHByZXZpZXcuYXR0cignc3JjJywgZS50YXJnZXQucmVzdWx0KS5zaG93KCk7XG4gICAgICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgICAgIHJlYWRlci5yZWFkQXNEYXRhVVJMKGV2ZW50LnRhcmdldC5maWxlc1swXSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIEFmZmljaGUgbGVzIGVycmV1cnMgZGUgZm9ybXVsYWlyZVxuICAgICAgICAgKi9cbiAgICAgICAgZGlzcGxheUZvcm1FcnJvcnM6IGZ1bmN0aW9uICgkZm9ybSwgZXJyb3JzKSB7XG4gICAgICAgICAgICAvLyBDbGVhciBwcmV2aW91cyBlcnJvcnNcbiAgICAgICAgICAgICRmb3JtLmZpbmQoJy5pcy1pbnZhbGlkJykucmVtb3ZlQ2xhc3MoJ2lzLWludmFsaWQnKTtcbiAgICAgICAgICAgICRmb3JtLmZpbmQoJy5pbnZhbGlkLWZlZWRiYWNrJykucmVtb3ZlKCk7XG5cbiAgICAgICAgICAgIC8vIERpc3BsYXkgbmV3IGVycm9yc1xuICAgICAgICAgICAgT2JqZWN0LmtleXMoZXJyb3JzKS5mb3JFYWNoKChmaWVsZCkgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0ICRmaWVsZCA9ICRmb3JtLmZpbmQoJ1tuYW1lPVwiJyArIGZpZWxkICsgJ1wiXScpO1xuICAgICAgICAgICAgICAgICRmaWVsZC5hZGRDbGFzcygnaXMtaW52YWxpZCcpO1xuICAgICAgICAgICAgICAgICRmaWVsZC5hZnRlcignPGRpdiBjbGFzcz1cImludmFsaWQtZmVlZGJhY2tcIj4nICsgZXJyb3JzW2ZpZWxkXSArICc8L2Rpdj4nKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBBZmZpY2hlIHVuIGxvYWRlclxuICAgICAgICAgKi9cbiAgICAgICAgc2hvd0xvYWRlcjogZnVuY3Rpb24gKCRlbGVtZW50KSB7XG4gICAgICAgICAgICBpZiAoJGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICAkZWxlbWVudC5hZGRDbGFzcygnbG9hZGluZycpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAkKCdib2R5JykuYWRkQ2xhc3MoJ2xvYWRpbmctb3ZlcmxheScpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBDYWNoZSBsZSBsb2FkZXJcbiAgICAgICAgICovXG4gICAgICAgIGhpZGVMb2FkZXI6IGZ1bmN0aW9uICgkZWxlbWVudCkge1xuICAgICAgICAgICAgaWYgKCRlbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgJGVsZW1lbnQucmVtb3ZlQ2xhc3MoJ2xvYWRpbmcnKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgJCgnYm9keScpLnJlbW92ZUNsYXNzKCdsb2FkaW5nLW92ZXJsYXknKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcblxuICAgICAgICAvKipcbiAgICAgICAgICogQWZmaWNoZSB1biBtZXNzYWdlIGRlIHN1Y2PDqHNcbiAgICAgICAgICovXG4gICAgICAgIHNob3dTdWNjZXNzOiBmdW5jdGlvbiAobWVzc2FnZSkge1xuICAgICAgICAgICAgaWYgKHR5cGVvZiBzaG93U3VjY2Vzc01lc3NhZ2UgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgICAgICBzaG93U3VjY2Vzc01lc3NhZ2UobWVzc2FnZSk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGFsZXJ0KG1lc3NhZ2UpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBBZmZpY2hlIHVuIG1lc3NhZ2UgZCdlcnJldXJcbiAgICAgICAgICovXG4gICAgICAgIHNob3dFcnJvcjogZnVuY3Rpb24gKG1lc3NhZ2UpIHtcbiAgICAgICAgICAgIGlmICh0eXBlb2Ygc2hvd0Vycm9yTWVzc2FnZSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgIHNob3dFcnJvck1lc3NhZ2UobWVzc2FnZSk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGFsZXJ0KCdFcnJvcjogJyArIG1lc3NhZ2UpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBBZmZpY2hlIHVuIGF2ZXJ0aXNzZW1lbnRcbiAgICAgICAgICovXG4gICAgICAgIHNob3dXYXJuaW5nOiBmdW5jdGlvbiAobWVzc2FnZSkge1xuICAgICAgICAgICAgYWxlcnQobWVzc2FnZSk7XG4gICAgICAgIH0sXG4gICAgfTtcblxuICAgIC8vIEluaXRpYWxpc2F0aW9uIGF1IGNoYXJnZW1lbnRcbiAgICAkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgICAgIFdlcHJlc3RhX1Bhc3N3b3JkbGVzc19Mb2dpbkFkbWluLmluaXQoKTtcbiAgICB9KTtcblxuICAgIC8vIEV4cG9ydCBnbG9iYWxcbiAgICB3aW5kb3cuV2VwcmVzdGFfUGFzc3dvcmRsZXNzX0xvZ2luQWRtaW4gPSBXZXByZXN0YV9QYXNzd29yZGxlc3NfTG9naW5BZG1pbjtcbn0pKHdpbmRvdy5qUXVlcnkgfHwgd2luZG93LiQpO1xuXG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiXSwibmFtZXMiOlsiJCIsIldlcHJlc3RhX1Bhc3N3b3JkbGVzc19Mb2dpbkFkbWluIiwiaW5pdCIsImJpbmRFdmVudHMiLCJpbml0U29ydGFibGUiLCJpbml0VG9vbHRpcHMiLCJjb25zb2xlIiwiZGVidWciLCJkb2N1bWVudCIsIm9uIiwiaGFuZGxlVG9nZ2xlIiwiYmluZCIsImhhbmRsZUJ1bGtBY3Rpb24iLCJoYW5kbGVEZWxldGUiLCJoYW5kbGVGb3JtU3VibWl0IiwiaGFuZGxlSW1hZ2VQcmV2aWV3IiwiaGFuZGxlQWpheEZvcm0iLCIkc29ydGFibGUiLCJsZW5ndGgiLCJmbiIsInNvcnRhYmxlIiwiaGFuZGxlIiwicGxhY2Vob2xkZXIiLCJ1cGRhdGUiLCJoYW5kbGVTb3J0VXBkYXRlIiwidG9vbHRpcCIsImV2ZW50IiwiJHRvZ2dsZSIsImN1cnJlbnRUYXJnZXQiLCJpdGVtSWQiLCJkYXRhIiwibmV3U3RhdGUiLCJpcyIsInNob3dMb2FkZXIiLCJjbG9zZXN0IiwiYWpheCIsInVybCIsIndpbmRvdyIsIm1vZHVsZVN0YXJ0ZXJBZG1pblVybCIsIm1ldGhvZCIsImFjdGlvbiIsImlkIiwiYWN0aXZlIiwic3VjY2VzcyIsInJlc3BvbnNlIiwic2hvd1N1Y2Nlc3MiLCJtZXNzYWdlIiwicHJvcCIsInNob3dFcnJvciIsImVycm9yIiwiY29tcGxldGUiLCJoaWRlTG9hZGVyIiwicHJldmVudERlZmF1bHQiLCIkYnV0dG9uIiwiJGNoZWNrZWQiLCJzaG93V2FybmluZyIsImlkcyIsIm1hcCIsInZhbCIsImdldCIsImNvbmZpcm0iLCJsb2NhdGlvbiIsInJlbG9hZCIsIiRyb3ciLCJhdHRyIiwiZmFkZU91dCIsInJlbW92ZSIsInVpIiwiJGl0ZW1zIiwidGFyZ2V0IiwiZmluZCIsInBvc2l0aW9ucyIsImVhY2giLCJpbmRleCIsInB1c2giLCJwb3NpdGlvbiIsIiRmb3JtIiwiaXNWYWxpZCIsImFkZENsYXNzIiwicmVtb3ZlQ2xhc3MiLCIkc3VibWl0Iiwic2VyaWFsaXplIiwicmVkaXJlY3QiLCJocmVmIiwiZXJyb3JzIiwiZGlzcGxheUZvcm1FcnJvcnMiLCIkaW5wdXQiLCIkcHJldmlldyIsInNpYmxpbmdzIiwiZmlsZXMiLCJyZWFkZXIiLCJGaWxlUmVhZGVyIiwib25sb2FkIiwiZSIsInJlc3VsdCIsInNob3ciLCJyZWFkQXNEYXRhVVJMIiwiT2JqZWN0Iiwia2V5cyIsImZvckVhY2giLCJmaWVsZCIsIiRmaWVsZCIsImFmdGVyIiwiJGVsZW1lbnQiLCJzaG93U3VjY2Vzc01lc3NhZ2UiLCJhbGVydCIsInNob3dFcnJvck1lc3NhZ2UiLCJyZWFkeSIsImpRdWVyeSJdLCJzb3VyY2VSb290IjoiIn0=