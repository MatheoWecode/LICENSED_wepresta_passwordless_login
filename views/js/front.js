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
(self["webpackChunkwepresta_passwordless_login"] = self["webpackChunkwepresta_passwordless_login"] || []).push([["front"],{

/***/ "./_dev/js/front.js":
/*!**************************!*\
  !*** ./_dev/js/front.js ***!
  \**************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
/* harmony import */ var core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_constructor_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/esnext.iterator.find.js */ "./node_modules/core-js/modules/esnext.iterator.find.js");
/* harmony import */ var core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_esnext_iterator_find_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _scss_front_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../scss/front.scss */ "./_dev/scss/front.scss");


/**
 * Passwordless Login - Front-office JavaScript
 * jQuery-based multi-step authentication flow
 */


(function ($) {
  'use strict';

  var PasswordlessLogin = {
    apiUrl: '',
    csrfToken: '',
    googleEnabled: false,
    googleClientId: '',
    resendCountdown: 60,
    backUrl: '',
    email: '',
    currentStep: 'email',
    countdownTimer: null,
    loading: false,
    init: function () {
      var $container = $('#passwordless-login');
      if (!$container.length) {
        return;
      }
      this.apiUrl = $container.data('api-url');
      this.csrfToken = $container.data('csrf-token');
      this.googleEnabled = !!$container.data('google-enabled');
      this.googleClientId = $container.data('google-client-id') || '';
      this.resendCountdown = parseInt($container.data('resend-countdown'), 10) || 60;
      this.backUrl = $container.data('back-url') || '';
      this.bindEvents();
      if (this.googleEnabled && this.googleClientId) {
        this.initGoogleSignIn();
      }

      // Handle browser back button
      var self = this;
      $(window).on('popstate', function (e) {
        var state = e.originalEvent.state;
        if (state && state.step) {
          self.showStep(state.step, true);
        }
      });

      // Push initial state
      history.replaceState({
        step: 'email'
      }, '', window.location.href);
    },
    bindEvents: function () {
      var self = this;

      // Email form
      $('#pl-email-form').on('submit', function (e) {
        e.preventDefault();
        self.sendCode();
      });

      // Code digit inputs
      $(document).on('input', '.pl-code-digit', function () {
        self.handleCodeInput($(this));
      });
      $(document).on('keydown', '.pl-code-digit', function (e) {
        self.handleCodeKeydown($(this), e);
      });
      $(document).on('paste', '.pl-code-digit', function (e) {
        e.preventDefault();
        var pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        self.handleCodePaste(pastedData);
      });

      // Profile form
      $('#pl-profile-form').on('submit', function (e) {
        e.preventDefault();
        self.completeProfile();
      });

      // Resend code
      $('#pl-resend-btn').on('click', function () {
        if (!$(this).prop('disabled')) {
          self.sendCode();
        }
      });

      // Back buttons
      $('#pl-back-to-email').on('click', function () {
        self.showStep('email');
      });
      $('#pl-back-to-code').on('click', function () {
        self.showStep('code');
      });
    },
    // ============================================
    // Step management
    // ============================================

    showStep: function (step, fromPopstate) {
      this.currentStep = step;
      $('.pl-step').hide();
      $('.pl-step[data-step="' + step + '"]').fadeIn(200);

      // Hide any error messages
      $('#pl-code-error').hide();
      if (!fromPopstate) {
        history.pushState({
          step: step
        }, '', window.location.href);
      }

      // Focus management
      if (step === 'email') {
        setTimeout(function () {
          $('#pl-email').focus();
        }, 250);
      } else if (step === 'code') {
        setTimeout(function () {
          $('.pl-code-digit').first().focus();
        }, 250);
      } else if (step === 'profile') {
        setTimeout(function () {
          $('#pl-firstname').focus();
        }, 250);
      }
    },
    // ============================================
    // Send code
    // ============================================

    sendCode: function () {
      var self = this;
      var email = $.trim($('#pl-email').val());
      if (!email || !this.isValidEmail(email)) {
        this.showFieldError($('#pl-email'));
        return;
      }
      this.email = email;
      this.setLoading('#pl-send-code-btn', true);
      $.ajax({
        url: this.apiUrl,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'sendCode',
          email: email,
          token: this.csrfToken
        },
        success: function (response) {
          if (response.success) {
            // Show code step
            $('#pl-code-subtitle').html($('#pl-code-subtitle').closest('.pl-card').find('.pl-title').text() ? self.email : self.email);
            // Set subtitle with the email
            var $subtitle = $('#pl-code-subtitle');
            $subtitle.html($subtitle.data('template') || 'Code sent to <strong>' + self.escapeHtml(self.email) + '</strong>');
            // Actually just set it directly
            $subtitle.html('Code sent to <strong>' + self.escapeHtml(self.email) + '</strong>');
            self.showStep('code');
            self.clearCodeInputs();
            self.startCountdown();
          } else {
            self.showMessage(response.error || 'An error occurred.', 'error');
          }
        },
        error: function () {
          self.showMessage('An error occurred. Please try again.', 'error');
        },
        complete: function () {
          self.setLoading('#pl-send-code-btn', false);
        }
      });
    },
    // ============================================
    // Code input handling
    // ============================================

    handleCodeInput: function ($input) {
      var val = $input.val().replace(/[^0-9]/g, '');
      $input.val(val);
      if (val.length === 1) {
        $input.addClass('pl-code-digit--filled').removeClass('pl-code-digit--error');
        // Move to next input
        var $next = $input.closest('.pl-code-inputs').find('.pl-code-digit[data-index="' + (parseInt($input.data('index'), 10) + 1) + '"]');
        if ($next.length) {
          $next.focus();
        }
      }

      // Check if all 6 digits are filled
      this.checkCodeComplete();
    },
    handleCodeKeydown: function ($input, e) {
      // Backspace: clear and move to previous
      if (e.key === 'Backspace') {
        if ($input.val() === '') {
          var $prev = $input.closest('.pl-code-inputs').find('.pl-code-digit[data-index="' + (parseInt($input.data('index'), 10) - 1) + '"]');
          if ($prev.length) {
            $prev.val('').removeClass('pl-code-digit--filled').focus();
          }
        } else {
          $input.val('').removeClass('pl-code-digit--filled');
        }
        e.preventDefault();
      }

      // Arrow left
      if (e.key === 'ArrowLeft') {
        var $prevArrow = $input.closest('.pl-code-inputs').find('.pl-code-digit[data-index="' + (parseInt($input.data('index'), 10) - 1) + '"]');
        if ($prevArrow.length) {
          $prevArrow.focus();
        }
      }

      // Arrow right
      if (e.key === 'ArrowRight') {
        var $nextArrow = $input.closest('.pl-code-inputs').find('.pl-code-digit[data-index="' + (parseInt($input.data('index'), 10) + 1) + '"]');
        if ($nextArrow.length) {
          $nextArrow.focus();
        }
      }
    },
    handleCodePaste: function (pastedData) {
      var digits = pastedData.replace(/[^0-9]/g, '').substring(0, 6);
      if (digits.length === 0) {
        return;
      }
      var $inputs = $('.pl-code-digit');
      for (var i = 0; i < digits.length && i < 6; i++) {
        $inputs.eq(i).val(digits[i]).addClass('pl-code-digit--filled');
      }

      // Focus the next empty input or the last one
      if (digits.length < 6) {
        $inputs.eq(digits.length).focus();
      } else {
        $inputs.eq(5).focus();
        this.checkCodeComplete();
      }
    },
    clearCodeInputs: function () {
      $('.pl-code-digit').val('').removeClass('pl-code-digit--filled pl-code-digit--error');
    },
    checkCodeComplete: function () {
      var code = '';
      var allFilled = true;
      $('.pl-code-digit').each(function () {
        var val = $(this).val();
        if (val.length !== 1) {
          allFilled = false;
        }
        code += val;
      });
      if (allFilled && code.length === 6) {
        this.verifyCode(code);
      }
    },
    // ============================================
    // Verify code
    // ============================================

    verifyCode: function (code) {
      var self = this;
      if (this.loading) {
        return;
      }
      this.loading = true;
      $.ajax({
        url: this.apiUrl,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'verifyCode',
          email: this.email,
          code: code,
          token: this.csrfToken
        },
        success: function (response) {
          if (response.success) {
            if (response.needsProfile) {
              // New user: show profile step
              // Pre-fill if data from Google
              if (response.firstName) {
                $('#pl-firstname').val(response.firstName);
              }
              if (response.lastName) {
                $('#pl-lastname').val(response.lastName);
              }
              self.showStep('profile');
            } else {
              // Existing user: redirect
              self.showMessage('Logged in successfully!', 'success');
              window.location.href = self.backUrl || response.redirectUrl || window.location.origin;
            }
          } else {
            self.showCodeError(response.error || 'Invalid code.');
            self.clearCodeInputs();
            setTimeout(function () {
              $('.pl-code-digit').first().focus();
            }, 100);
          }
        },
        error: function () {
          self.showCodeError('An error occurred. Please try again.');
          self.clearCodeInputs();
        },
        complete: function () {
          self.loading = false;
        }
      });
    },
    showCodeError: function (message) {
      var $error = $('#pl-code-error');
      $error.text(message).show();
      $('.pl-code-digit').addClass('pl-code-digit--error');
    },
    // ============================================
    // Complete profile
    // ============================================

    completeProfile: function () {
      var self = this;
      var firstName = $.trim($('#pl-firstname').val());
      var lastName = $.trim($('#pl-lastname').val());
      if (!firstName) {
        this.showFieldError($('#pl-firstname'));
        return;
      }
      if (!lastName) {
        this.showFieldError($('#pl-lastname'));
        return;
      }
      this.setLoading('#pl-complete-btn', true);
      $.ajax({
        url: this.apiUrl,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'completeProfile',
          email: this.email,
          firstname: firstName,
          lastname: lastName,
          token: this.csrfToken
        },
        success: function (response) {
          if (response.success) {
            self.showMessage('Account created!', 'success');
            window.location.href = self.backUrl || response.redirectUrl || window.location.origin;
          } else {
            self.showMessage(response.error || 'An error occurred.', 'error');
          }
        },
        error: function () {
          self.showMessage('An error occurred. Please try again.', 'error');
        },
        complete: function () {
          self.setLoading('#pl-complete-btn', false);
        }
      });
    },
    // ============================================
    // Google Sign-In
    // ============================================

    initGoogleSignIn: function () {
      var self = this;
      var script = document.createElement('script');
      script.src = 'https://accounts.google.com/gsi/client';
      script.async = true;
      script.defer = true;
      script.onload = function () {
        if (typeof google !== 'undefined' && google.accounts) {
          google.accounts.id.initialize({
            client_id: self.googleClientId,
            callback: function (response) {
              self.handleGoogleAuth(response.credential);
            }
          });

          // Render a custom button handler
          $('#pl-google-btn').on('click', function () {
            google.accounts.id.prompt();
          });
        }
      };
      document.head.appendChild(script);
    },
    handleGoogleAuth: function (credential) {
      var self = this;
      this.setLoading('#pl-google-btn', true);
      $.ajax({
        url: this.apiUrl,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'googleAuth',
          credential: credential,
          token: this.csrfToken
        },
        success: function (response) {
          if (response.success) {
            if (response.needsProfile) {
              self.email = response.email || '';
              if (response.firstName) {
                $('#pl-firstname').val(response.firstName);
              }
              if (response.lastName) {
                $('#pl-lastname').val(response.lastName);
              }
              self.showStep('profile');
            } else {
              self.showMessage('Logged in successfully!', 'success');
              window.location.href = self.backUrl || response.redirectUrl || window.location.origin;
            }
          } else {
            self.showMessage(response.error || 'Google authentication failed.', 'error');
          }
        },
        error: function () {
          self.showMessage('An error occurred. Please try again.', 'error');
        },
        complete: function () {
          self.setLoading('#pl-google-btn', false);
        }
      });
    },
    // ============================================
    // Countdown timer
    // ============================================

    startCountdown: function () {
      var self = this;
      var seconds = this.resendCountdown;
      var $btn = $('#pl-resend-btn');
      var $countdown = $('#pl-countdown');
      var $wrap = $('#pl-countdown-wrap');
      $btn.prop('disabled', true);
      $wrap.show();
      if (this.countdownTimer) {
        clearInterval(this.countdownTimer);
      }
      var updateDisplay = function () {
        var mins = Math.floor(seconds / 60);
        var secs = seconds % 60;
        $countdown.text(mins + ':' + (secs < 10 ? '0' : '') + secs);
      };
      updateDisplay();
      this.countdownTimer = setInterval(function () {
        seconds--;
        if (seconds <= 0) {
          clearInterval(self.countdownTimer);
          $btn.prop('disabled', false);
          $wrap.hide();
        } else {
          updateDisplay();
        }
      }, 1000);
    },
    // ============================================
    // Utilities
    // ============================================

    setLoading: function (selector, isLoading) {
      var $btn = $(selector);
      if (isLoading) {
        $btn.prop('disabled', true);
        $btn.find('.pl-btn__text').hide();
        $btn.find('.pl-btn__loader').show();
      } else {
        $btn.prop('disabled', false);
        $btn.find('.pl-btn__text').show();
        $btn.find('.pl-btn__loader').hide();
      }
    },
    showFieldError: function ($input) {
      $input.addClass('pl-input--error');
      $input.one('input', function () {
        $(this).removeClass('pl-input--error');
      });
      $input.focus();
    },
    showMessage: function (text, type) {
      var $msg = $('#pl-message');
      $msg.text(text).removeClass('pl-message--error pl-message--success').addClass('pl-message--' + type).show();
      setTimeout(function () {
        $msg.fadeOut(200);
      }, 4000);
    },
    isValidEmail: function (email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },
    escapeHtml: function (str) {
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    }
  };
  $(document).ready(function () {
    PasswordlessLogin.init();
  });
  window.PasswordlessLogin = PasswordlessLogin;
})(jQuery);

/***/ }),

/***/ "./_dev/scss/front.scss":
/*!******************************!*\
  !*** ./_dev/scss/front.scss ***!
  \******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ var __webpack_exec__ = function(moduleId) { return __webpack_require__(__webpack_require__.s = moduleId); }
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_modules_esnext_iterator_constructor_js-node_modules_core-js_modu-bd769a"], function() { return __webpack_exec__("./_dev/js/front.js"); });
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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LHVDQUF1QyxDQUFDLENBQ3BERCxRQUFRLENBQUMsY0FBYyxHQUFHMkQsSUFBSSxDQUFDLENBQy9CbEMsSUFBSSxDQUFDLENBQUM7TUFFWHRELFVBQVUsQ0FBQyxZQUFZO1FBQ25CeUYsSUFBSSxDQUFDQyxPQUFPLENBQUMsR0FBRyxDQUFDO01BQ3JCLENBQUMsRUFBRSxJQUFJLENBQUM7SUFDWixDQUFDO0lBRURyRixZQUFZLEVBQUUsU0FBQUEsQ0FBVTNDLEtBQUssRUFBRTtNQUMzQixPQUFPLDRCQUE0QixDQUFDaUksSUFBSSxDQUFDakksS0FBSyxDQUFDO0lBQ25ELENBQUM7SUFFRDJELFVBQVUsRUFBRSxTQUFBQSxDQUFVdUUsR0FBRyxFQUFFO01BQ3ZCLElBQUlDLEdBQUcsR0FBRzFHLFFBQVEsQ0FBQ3VFLGFBQWEsQ0FBQyxLQUFLLENBQUM7TUFDdkNtQyxHQUFHLENBQUNwQixXQUFXLENBQUN0RixRQUFRLENBQUMyRyxjQUFjLENBQUNGLEdBQUcsQ0FBQyxDQUFDO01BQzdDLE9BQU9DLEdBQUcsQ0FBQ0UsU0FBUztJQUN4QjtFQUNKLENBQUM7RUFFRDdJLENBQUMsQ0FBQ2lDLFFBQVEsQ0FBQyxDQUFDNkcsS0FBSyxDQUFDLFlBQVk7SUFDMUI3SSxpQkFBaUIsQ0FBQ1csSUFBSSxDQUFDLENBQUM7RUFDNUIsQ0FBQyxDQUFDO0VBRUZRLE1BQU0sQ0FBQ25CLGlCQUFpQixHQUFHQSxpQkFBaUI7QUFFaEQsQ0FBQyxFQUFFOEksTUFBTSxDQUFDLEM7Ozs7Ozs7Ozs7O0FDamlCViIsInNvdXJjZXMiOlsid2VicGFjazovL3dlcHJlc3RhX3Bhc3N3b3JkbGVzc19sb2dpbi8uL19kZXYvanMvZnJvbnQuanMiLCJ3ZWJwYWNrOi8vd2VwcmVzdGFfcGFzc3dvcmRsZXNzX2xvZ2luLy4vX2Rldi9zY3NzL2Zyb250LnNjc3M/OTYyZCJdLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIFBhc3N3b3JkbGVzcyBMb2dpbiAtIEZyb250LW9mZmljZSBKYXZhU2NyaXB0XG4gKiBqUXVlcnktYmFzZWQgbXVsdGktc3RlcCBhdXRoZW50aWNhdGlvbiBmbG93XG4gKi9cblxuaW1wb3J0ICcuLi9zY3NzL2Zyb250LnNjc3MnO1xuXG4oZnVuY3Rpb24gKCQpIHtcbiAgICAndXNlIHN0cmljdCc7XG5cbiAgICB2YXIgUGFzc3dvcmRsZXNzTG9naW4gPSB7XG4gICAgICAgIGFwaVVybDogJycsXG4gICAgICAgIGNzcmZUb2tlbjogJycsXG4gICAgICAgIGdvb2dsZUVuYWJsZWQ6IGZhbHNlLFxuICAgICAgICBnb29nbGVDbGllbnRJZDogJycsXG4gICAgICAgIHJlc2VuZENvdW50ZG93bjogNjAsXG4gICAgICAgIGJhY2tVcmw6ICcnLFxuICAgICAgICBlbWFpbDogJycsXG4gICAgICAgIGN1cnJlbnRTdGVwOiAnZW1haWwnLFxuICAgICAgICBjb3VudGRvd25UaW1lcjogbnVsbCxcbiAgICAgICAgbG9hZGluZzogZmFsc2UsXG5cbiAgICAgICAgaW5pdDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyICRjb250YWluZXIgPSAkKCcjcGFzc3dvcmRsZXNzLWxvZ2luJyk7XG4gICAgICAgICAgICBpZiAoISRjb250YWluZXIubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB0aGlzLmFwaVVybCA9ICRjb250YWluZXIuZGF0YSgnYXBpLXVybCcpO1xuICAgICAgICAgICAgdGhpcy5jc3JmVG9rZW4gPSAkY29udGFpbmVyLmRhdGEoJ2NzcmYtdG9rZW4nKTtcbiAgICAgICAgICAgIHRoaXMuZ29vZ2xlRW5hYmxlZCA9ICEhJGNvbnRhaW5lci5kYXRhKCdnb29nbGUtZW5hYmxlZCcpO1xuICAgICAgICAgICAgdGhpcy5nb29nbGVDbGllbnRJZCA9ICRjb250YWluZXIuZGF0YSgnZ29vZ2xlLWNsaWVudC1pZCcpIHx8ICcnO1xuICAgICAgICAgICAgdGhpcy5yZXNlbmRDb3VudGRvd24gPSBwYXJzZUludCgkY29udGFpbmVyLmRhdGEoJ3Jlc2VuZC1jb3VudGRvd24nKSwgMTApIHx8IDYwO1xuICAgICAgICAgICAgdGhpcy5iYWNrVXJsID0gJGNvbnRhaW5lci5kYXRhKCdiYWNrLXVybCcpIHx8ICcnO1xuXG4gICAgICAgICAgICB0aGlzLmJpbmRFdmVudHMoKTtcblxuICAgICAgICAgICAgaWYgKHRoaXMuZ29vZ2xlRW5hYmxlZCAmJiB0aGlzLmdvb2dsZUNsaWVudElkKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5pbml0R29vZ2xlU2lnbkluKCk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vIEhhbmRsZSBicm93c2VyIGJhY2sgYnV0dG9uXG4gICAgICAgICAgICB2YXIgc2VsZiA9IHRoaXM7XG4gICAgICAgICAgICAkKHdpbmRvdykub24oJ3BvcHN0YXRlJywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAgICAgICB2YXIgc3RhdGUgPSBlLm9yaWdpbmFsRXZlbnQuc3RhdGU7XG4gICAgICAgICAgICAgICAgaWYgKHN0YXRlICYmIHN0YXRlLnN0ZXApIHtcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5zaG93U3RlcChzdGF0ZS5zdGVwLCB0cnVlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy8gUHVzaCBpbml0aWFsIHN0YXRlXG4gICAgICAgICAgICBoaXN0b3J5LnJlcGxhY2VTdGF0ZSh7IHN0ZXA6ICdlbWFpbCcgfSwgJycsIHdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgfSxcblxuICAgICAgICBiaW5kRXZlbnRzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgc2VsZiA9IHRoaXM7XG5cbiAgICAgICAgICAgIC8vIEVtYWlsIGZvcm1cbiAgICAgICAgICAgICQoJyNwbC1lbWFpbC1mb3JtJykub24oJ3N1Ym1pdCcsIGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgIHNlbGYuc2VuZENvZGUoKTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAvLyBDb2RlIGRpZ2l0IGlucHV0c1xuICAgICAgICAgICAgJChkb2N1bWVudCkub24oJ2lucHV0JywgJy5wbC1jb2RlLWRpZ2l0JywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHNlbGYuaGFuZGxlQ29kZUlucHV0KCQodGhpcykpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICQoZG9jdW1lbnQpLm9uKCdrZXlkb3duJywgJy5wbC1jb2RlLWRpZ2l0JywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAgICAgICBzZWxmLmhhbmRsZUNvZGVLZXlkb3duKCQodGhpcyksIGUpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICQoZG9jdW1lbnQpLm9uKCdwYXN0ZScsICcucGwtY29kZS1kaWdpdCcsIGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgIHZhciBwYXN0ZWREYXRhID0gKGUub3JpZ2luYWxFdmVudC5jbGlwYm9hcmREYXRhIHx8IHdpbmRvdy5jbGlwYm9hcmREYXRhKS5nZXREYXRhKCd0ZXh0Jyk7XG4gICAgICAgICAgICAgICAgc2VsZi5oYW5kbGVDb2RlUGFzdGUocGFzdGVkRGF0YSk7XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy8gUHJvZmlsZSBmb3JtXG4gICAgICAgICAgICAkKCcjcGwtcHJvZmlsZS1mb3JtJykub24oJ3N1Ym1pdCcsIGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgIHNlbGYuY29tcGxldGVQcm9maWxlKCk7XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy8gUmVzZW5kIGNvZGVcbiAgICAgICAgICAgICQoJyNwbC1yZXNlbmQtYnRuJykub24oJ2NsaWNrJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIGlmICghJCh0aGlzKS5wcm9wKCdkaXNhYmxlZCcpKSB7XG4gICAgICAgICAgICAgICAgICAgIHNlbGYuc2VuZENvZGUoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy8gQmFjayBidXR0b25zXG4gICAgICAgICAgICAkKCcjcGwtYmFjay10by1lbWFpbCcpLm9uKCdjbGljaycsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICBzZWxmLnNob3dTdGVwKCdlbWFpbCcpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICQoJyNwbC1iYWNrLXRvLWNvZGUnKS5vbignY2xpY2snLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgc2VsZi5zaG93U3RlcCgnY29kZScpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLy8gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgLy8gU3RlcCBtYW5hZ2VtZW50XG4gICAgICAgIC8vID09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG5cbiAgICAgICAgc2hvd1N0ZXA6IGZ1bmN0aW9uIChzdGVwLCBmcm9tUG9wc3RhdGUpIHtcbiAgICAgICAgICAgIHRoaXMuY3VycmVudFN0ZXAgPSBzdGVwO1xuXG4gICAgICAgICAgICAkKCcucGwtc3RlcCcpLmhpZGUoKTtcbiAgICAgICAgICAgICQoJy5wbC1zdGVwW2RhdGEtc3RlcD1cIicgKyBzdGVwICsgJ1wiXScpLmZhZGVJbigyMDApO1xuXG4gICAgICAgICAgICAvLyBIaWRlIGFueSBlcnJvciBtZXNzYWdlc1xuICAgICAgICAgICAgJCgnI3BsLWNvZGUtZXJyb3InKS5oaWRlKCk7XG5cbiAgICAgICAgICAgIGlmICghZnJvbVBvcHN0YXRlKSB7XG4gICAgICAgICAgICAgICAgaGlzdG9yeS5wdXNoU3RhdGUoeyBzdGVwOiBzdGVwIH0sICcnLCB3aW5kb3cubG9jYXRpb24uaHJlZik7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vIEZvY3VzIG1hbmFnZW1lbnRcbiAgICAgICAgICAgIGlmIChzdGVwID09PSAnZW1haWwnKSB7XG4gICAgICAgICAgICAgICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7ICQoJyNwbC1lbWFpbCcpLmZvY3VzKCk7IH0sIDI1MCk7XG4gICAgICAgICAgICB9IGVsc2UgaWYgKHN0ZXAgPT09ICdjb2RlJykge1xuICAgICAgICAgICAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkgeyAkKCcucGwtY29kZS1kaWdpdCcpLmZpcnN0KCkuZm9jdXMoKTsgfSwgMjUwKTtcbiAgICAgICAgICAgIH0gZWxzZSBpZiAoc3RlcCA9PT0gJ3Byb2ZpbGUnKSB7XG4gICAgICAgICAgICAgICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7ICQoJyNwbC1maXJzdG5hbWUnKS5mb2N1cygpOyB9LCAyNTApO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8vID09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG4gICAgICAgIC8vIFNlbmQgY29kZVxuICAgICAgICAvLyA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuXG4gICAgICAgIHNlbmRDb2RlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgc2VsZiA9IHRoaXM7XG4gICAgICAgICAgICB2YXIgZW1haWwgPSAkLnRyaW0oJCgnI3BsLWVtYWlsJykudmFsKCkpO1xuXG4gICAgICAgICAgICBpZiAoIWVtYWlsIHx8ICF0aGlzLmlzVmFsaWRFbWFpbChlbWFpbCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnNob3dGaWVsZEVycm9yKCQoJyNwbC1lbWFpbCcpKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHRoaXMuZW1haWwgPSBlbWFpbDtcbiAgICAgICAgICAgIHRoaXMuc2V0TG9hZGluZygnI3BsLXNlbmQtY29kZS1idG4nLCB0cnVlKTtcblxuICAgICAgICAgICAgJC5hamF4KHtcbiAgICAgICAgICAgICAgICB1cmw6IHRoaXMuYXBpVXJsLFxuICAgICAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxuICAgICAgICAgICAgICAgIGRhdGFUeXBlOiAnanNvbicsXG4gICAgICAgICAgICAgICAgZGF0YToge1xuICAgICAgICAgICAgICAgICAgICBhY3Rpb246ICdzZW5kQ29kZScsXG4gICAgICAgICAgICAgICAgICAgIGVtYWlsOiBlbWFpbCxcbiAgICAgICAgICAgICAgICAgICAgdG9rZW46IHRoaXMuY3NyZlRva2VuXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbiAocmVzcG9uc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHJlc3BvbnNlLnN1Y2Nlc3MpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIFNob3cgY29kZSBzdGVwXG4gICAgICAgICAgICAgICAgICAgICAgICAkKCcjcGwtY29kZS1zdWJ0aXRsZScpLmh0bWwoXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnI3BsLWNvZGUtc3VidGl0bGUnKS5jbG9zZXN0KCcucGwtY2FyZCcpLmZpbmQoJy5wbC10aXRsZScpLnRleHQoKSA/XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5lbWFpbCA6IHNlbGYuZW1haWxcbiAgICAgICAgICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyBTZXQgc3VidGl0bGUgd2l0aCB0aGUgZW1haWxcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciAkc3VidGl0bGUgPSAkKCcjcGwtY29kZS1zdWJ0aXRsZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgJHN1YnRpdGxlLmh0bWwoJHN1YnRpdGxlLmRhdGEoJ3RlbXBsYXRlJykgfHwgKCdDb2RlIHNlbnQgdG8gPHN0cm9uZz4nICsgc2VsZi5lc2NhcGVIdG1sKHNlbGYuZW1haWwpICsgJzwvc3Ryb25nPicpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIEFjdHVhbGx5IGp1c3Qgc2V0IGl0IGRpcmVjdGx5XG4gICAgICAgICAgICAgICAgICAgICAgICAkc3VidGl0bGUuaHRtbCgnQ29kZSBzZW50IHRvIDxzdHJvbmc+JyArIHNlbGYuZXNjYXBlSHRtbChzZWxmLmVtYWlsKSArICc8L3N0cm9uZz4nKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5zaG93U3RlcCgnY29kZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5jbGVhckNvZGVJbnB1dHMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc3RhcnRDb3VudGRvd24oKTtcbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc2hvd01lc3NhZ2UocmVzcG9uc2UuZXJyb3IgfHwgJ0FuIGVycm9yIG9jY3VycmVkLicsICdlcnJvcicpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBlcnJvcjogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICBzZWxmLnNob3dNZXNzYWdlKCdBbiBlcnJvciBvY2N1cnJlZC4gUGxlYXNlIHRyeSBhZ2Fpbi4nLCAnZXJyb3InKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGNvbXBsZXRlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgIHNlbGYuc2V0TG9hZGluZygnI3BsLXNlbmQtY29kZS1idG4nLCBmYWxzZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLy8gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgLy8gQ29kZSBpbnB1dCBoYW5kbGluZ1xuICAgICAgICAvLyA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuXG4gICAgICAgIGhhbmRsZUNvZGVJbnB1dDogZnVuY3Rpb24gKCRpbnB1dCkge1xuICAgICAgICAgICAgdmFyIHZhbCA9ICRpbnB1dC52YWwoKS5yZXBsYWNlKC9bXjAtOV0vZywgJycpO1xuICAgICAgICAgICAgJGlucHV0LnZhbCh2YWwpO1xuXG4gICAgICAgICAgICBpZiAodmFsLmxlbmd0aCA9PT0gMSkge1xuICAgICAgICAgICAgICAgICRpbnB1dC5hZGRDbGFzcygncGwtY29kZS1kaWdpdC0tZmlsbGVkJykucmVtb3ZlQ2xhc3MoJ3BsLWNvZGUtZGlnaXQtLWVycm9yJyk7XG4gICAgICAgICAgICAgICAgLy8gTW92ZSB0byBuZXh0IGlucHV0XG4gICAgICAgICAgICAgICAgdmFyICRuZXh0ID0gJGlucHV0LmNsb3Nlc3QoJy5wbC1jb2RlLWlucHV0cycpLmZpbmQoJy5wbC1jb2RlLWRpZ2l0W2RhdGEtaW5kZXg9XCInICsgKHBhcnNlSW50KCRpbnB1dC5kYXRhKCdpbmRleCcpLCAxMCkgKyAxKSArICdcIl0nKTtcbiAgICAgICAgICAgICAgICBpZiAoJG5leHQubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgICAgICRuZXh0LmZvY3VzKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAvLyBDaGVjayBpZiBhbGwgNiBkaWdpdHMgYXJlIGZpbGxlZFxuICAgICAgICAgICAgdGhpcy5jaGVja0NvZGVDb21wbGV0ZSgpO1xuICAgICAgICB9LFxuXG4gICAgICAgIGhhbmRsZUNvZGVLZXlkb3duOiBmdW5jdGlvbiAoJGlucHV0LCBlKSB7XG4gICAgICAgICAgICAvLyBCYWNrc3BhY2U6IGNsZWFyIGFuZCBtb3ZlIHRvIHByZXZpb3VzXG4gICAgICAgICAgICBpZiAoZS5rZXkgPT09ICdCYWNrc3BhY2UnKSB7XG4gICAgICAgICAgICAgICAgaWYgKCRpbnB1dC52YWwoKSA9PT0gJycpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyICRwcmV2ID0gJGlucHV0LmNsb3Nlc3QoJy5wbC1jb2RlLWlucHV0cycpLmZpbmQoJy5wbC1jb2RlLWRpZ2l0W2RhdGEtaW5kZXg9XCInICsgKHBhcnNlSW50KCRpbnB1dC5kYXRhKCdpbmRleCcpLCAxMCkgLSAxKSArICdcIl0nKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCRwcmV2Lmxlbmd0aCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgJHByZXYudmFsKCcnKS5yZW1vdmVDbGFzcygncGwtY29kZS1kaWdpdC0tZmlsbGVkJykuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICRpbnB1dC52YWwoJycpLnJlbW92ZUNsYXNzKCdwbC1jb2RlLWRpZ2l0LS1maWxsZWQnKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAvLyBBcnJvdyBsZWZ0XG4gICAgICAgICAgICBpZiAoZS5rZXkgPT09ICdBcnJvd0xlZnQnKSB7XG4gICAgICAgICAgICAgICAgdmFyICRwcmV2QXJyb3cgPSAkaW5wdXQuY2xvc2VzdCgnLnBsLWNvZGUtaW5wdXRzJykuZmluZCgnLnBsLWNvZGUtZGlnaXRbZGF0YS1pbmRleD1cIicgKyAocGFyc2VJbnQoJGlucHV0LmRhdGEoJ2luZGV4JyksIDEwKSAtIDEpICsgJ1wiXScpO1xuICAgICAgICAgICAgICAgIGlmICgkcHJldkFycm93Lmxlbmd0aCkge1xuICAgICAgICAgICAgICAgICAgICAkcHJldkFycm93LmZvY3VzKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAvLyBBcnJvdyByaWdodFxuICAgICAgICAgICAgaWYgKGUua2V5ID09PSAnQXJyb3dSaWdodCcpIHtcbiAgICAgICAgICAgICAgICB2YXIgJG5leHRBcnJvdyA9ICRpbnB1dC5jbG9zZXN0KCcucGwtY29kZS1pbnB1dHMnKS5maW5kKCcucGwtY29kZS1kaWdpdFtkYXRhLWluZGV4PVwiJyArIChwYXJzZUludCgkaW5wdXQuZGF0YSgnaW5kZXgnKSwgMTApICsgMSkgKyAnXCJdJyk7XG4gICAgICAgICAgICAgICAgaWYgKCRuZXh0QXJyb3cubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgICAgICRuZXh0QXJyb3cuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG5cbiAgICAgICAgaGFuZGxlQ29kZVBhc3RlOiBmdW5jdGlvbiAocGFzdGVkRGF0YSkge1xuICAgICAgICAgICAgdmFyIGRpZ2l0cyA9IHBhc3RlZERhdGEucmVwbGFjZSgvW14wLTldL2csICcnKS5zdWJzdHJpbmcoMCwgNik7XG4gICAgICAgICAgICBpZiAoZGlnaXRzLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdmFyICRpbnB1dHMgPSAkKCcucGwtY29kZS1kaWdpdCcpO1xuICAgICAgICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCBkaWdpdHMubGVuZ3RoICYmIGkgPCA2OyBpKyspIHtcbiAgICAgICAgICAgICAgICAkaW5wdXRzLmVxKGkpLnZhbChkaWdpdHNbaV0pLmFkZENsYXNzKCdwbC1jb2RlLWRpZ2l0LS1maWxsZWQnKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gRm9jdXMgdGhlIG5leHQgZW1wdHkgaW5wdXQgb3IgdGhlIGxhc3Qgb25lXG4gICAgICAgICAgICBpZiAoZGlnaXRzLmxlbmd0aCA8IDYpIHtcbiAgICAgICAgICAgICAgICAkaW5wdXRzLmVxKGRpZ2l0cy5sZW5ndGgpLmZvY3VzKCk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICRpbnB1dHMuZXEoNSkuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICB0aGlzLmNoZWNrQ29kZUNvbXBsZXRlKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG5cbiAgICAgICAgY2xlYXJDb2RlSW5wdXRzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAkKCcucGwtY29kZS1kaWdpdCcpLnZhbCgnJykucmVtb3ZlQ2xhc3MoJ3BsLWNvZGUtZGlnaXQtLWZpbGxlZCBwbC1jb2RlLWRpZ2l0LS1lcnJvcicpO1xuICAgICAgICB9LFxuXG4gICAgICAgIGNoZWNrQ29kZUNvbXBsZXRlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgY29kZSA9ICcnO1xuICAgICAgICAgICAgdmFyIGFsbEZpbGxlZCA9IHRydWU7XG4gICAgICAgICAgICAkKCcucGwtY29kZS1kaWdpdCcpLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHZhciB2YWwgPSAkKHRoaXMpLnZhbCgpO1xuICAgICAgICAgICAgICAgIGlmICh2YWwubGVuZ3RoICE9PSAxKSB7XG4gICAgICAgICAgICAgICAgICAgIGFsbEZpbGxlZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBjb2RlICs9IHZhbDtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICBpZiAoYWxsRmlsbGVkICYmIGNvZGUubGVuZ3RoID09PSA2KSB7XG4gICAgICAgICAgICAgICAgdGhpcy52ZXJpZnlDb2RlKGNvZGUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8vID09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG4gICAgICAgIC8vIFZlcmlmeSBjb2RlXG4gICAgICAgIC8vID09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG5cbiAgICAgICAgdmVyaWZ5Q29kZTogZnVuY3Rpb24gKGNvZGUpIHtcbiAgICAgICAgICAgIHZhciBzZWxmID0gdGhpcztcblxuICAgICAgICAgICAgaWYgKHRoaXMubG9hZGluZykge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRoaXMubG9hZGluZyA9IHRydWU7XG5cbiAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgdXJsOiB0aGlzLmFwaVVybCxcbiAgICAgICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgICAgIGRhdGE6IHtcbiAgICAgICAgICAgICAgICAgICAgYWN0aW9uOiAndmVyaWZ5Q29kZScsXG4gICAgICAgICAgICAgICAgICAgIGVtYWlsOiB0aGlzLmVtYWlsLFxuICAgICAgICAgICAgICAgICAgICBjb2RlOiBjb2RlLFxuICAgICAgICAgICAgICAgICAgICB0b2tlbjogdGhpcy5jc3JmVG9rZW5cbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uIChyZXNwb25zZSkge1xuICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2Uuc3VjY2Vzcykge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlc3BvbnNlLm5lZWRzUHJvZmlsZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIE5ldyB1c2VyOiBzaG93IHByb2ZpbGUgc3RlcFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIFByZS1maWxsIGlmIGRhdGEgZnJvbSBHb29nbGVcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2UuZmlyc3ROYW1lKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJyNwbC1maXJzdG5hbWUnKS52YWwocmVzcG9uc2UuZmlyc3ROYW1lKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHJlc3BvbnNlLmxhc3ROYW1lKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICQoJyNwbC1sYXN0bmFtZScpLnZhbChyZXNwb25zZS5sYXN0TmFtZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc2hvd1N0ZXAoJ3Byb2ZpbGUnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gRXhpc3RpbmcgdXNlcjogcmVkaXJlY3RcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWxmLnNob3dNZXNzYWdlKCdMb2dnZWQgaW4gc3VjY2Vzc2Z1bGx5IScsICdzdWNjZXNzJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LmxvY2F0aW9uLmhyZWYgPSBzZWxmLmJhY2tVcmwgfHwgcmVzcG9uc2UucmVkaXJlY3RVcmwgfHwgd2luZG93LmxvY2F0aW9uLm9yaWdpbjtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc2hvd0NvZGVFcnJvcihyZXNwb25zZS5lcnJvciB8fCAnSW52YWxpZCBjb2RlLicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5jbGVhckNvZGVJbnB1dHMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkgeyAkKCcucGwtY29kZS1kaWdpdCcpLmZpcnN0KCkuZm9jdXMoKTsgfSwgMTAwKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgZXJyb3I6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5zaG93Q29kZUVycm9yKCdBbiBlcnJvciBvY2N1cnJlZC4gUGxlYXNlIHRyeSBhZ2Fpbi4nKTtcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5jbGVhckNvZGVJbnB1dHMoKTtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGNvbXBsZXRlOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgIHNlbGYubG9hZGluZyA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9LFxuXG4gICAgICAgIHNob3dDb2RlRXJyb3I6IGZ1bmN0aW9uIChtZXNzYWdlKSB7XG4gICAgICAgICAgICB2YXIgJGVycm9yID0gJCgnI3BsLWNvZGUtZXJyb3InKTtcbiAgICAgICAgICAgICRlcnJvci50ZXh0KG1lc3NhZ2UpLnNob3coKTtcbiAgICAgICAgICAgICQoJy5wbC1jb2RlLWRpZ2l0JykuYWRkQ2xhc3MoJ3BsLWNvZGUtZGlnaXQtLWVycm9yJyk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLy8gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgLy8gQ29tcGxldGUgcHJvZmlsZVxuICAgICAgICAvLyA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuXG4gICAgICAgIGNvbXBsZXRlUHJvZmlsZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIHNlbGYgPSB0aGlzO1xuICAgICAgICAgICAgdmFyIGZpcnN0TmFtZSA9ICQudHJpbSgkKCcjcGwtZmlyc3RuYW1lJykudmFsKCkpO1xuICAgICAgICAgICAgdmFyIGxhc3ROYW1lID0gJC50cmltKCQoJyNwbC1sYXN0bmFtZScpLnZhbCgpKTtcblxuICAgICAgICAgICAgaWYgKCFmaXJzdE5hbWUpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnNob3dGaWVsZEVycm9yKCQoJyNwbC1maXJzdG5hbWUnKSk7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKCFsYXN0TmFtZSkge1xuICAgICAgICAgICAgICAgIHRoaXMuc2hvd0ZpZWxkRXJyb3IoJCgnI3BsLWxhc3RuYW1lJykpO1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdGhpcy5zZXRMb2FkaW5nKCcjcGwtY29tcGxldGUtYnRuJywgdHJ1ZSk7XG5cbiAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgdXJsOiB0aGlzLmFwaVVybCxcbiAgICAgICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgICAgIGRhdGE6IHtcbiAgICAgICAgICAgICAgICAgICAgYWN0aW9uOiAnY29tcGxldGVQcm9maWxlJyxcbiAgICAgICAgICAgICAgICAgICAgZW1haWw6IHRoaXMuZW1haWwsXG4gICAgICAgICAgICAgICAgICAgIGZpcnN0bmFtZTogZmlyc3ROYW1lLFxuICAgICAgICAgICAgICAgICAgICBsYXN0bmFtZTogbGFzdE5hbWUsXG4gICAgICAgICAgICAgICAgICAgIHRva2VuOiB0aGlzLmNzcmZUb2tlblxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24gKHJlc3BvbnNlKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5zdWNjZXNzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLnNob3dNZXNzYWdlKCdBY2NvdW50IGNyZWF0ZWQhJywgJ3N1Y2Nlc3MnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gc2VsZi5iYWNrVXJsIHx8IHJlc3BvbnNlLnJlZGlyZWN0VXJsIHx8IHdpbmRvdy5sb2NhdGlvbi5vcmlnaW47XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZWxmLnNob3dNZXNzYWdlKHJlc3BvbnNlLmVycm9yIHx8ICdBbiBlcnJvciBvY2N1cnJlZC4nLCAnZXJyb3InKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgZXJyb3I6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5zaG93TWVzc2FnZSgnQW4gZXJyb3Igb2NjdXJyZWQuIFBsZWFzZSB0cnkgYWdhaW4uJywgJ2Vycm9yJyk7XG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICBjb21wbGV0ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICBzZWxmLnNldExvYWRpbmcoJyNwbC1jb21wbGV0ZS1idG4nLCBmYWxzZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgLy8gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgLy8gR29vZ2xlIFNpZ24tSW5cbiAgICAgICAgLy8gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cblxuICAgICAgICBpbml0R29vZ2xlU2lnbkluOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgc2VsZiA9IHRoaXM7XG4gICAgICAgICAgICB2YXIgc2NyaXB0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnc2NyaXB0Jyk7XG4gICAgICAgICAgICBzY3JpcHQuc3JjID0gJ2h0dHBzOi8vYWNjb3VudHMuZ29vZ2xlLmNvbS9nc2kvY2xpZW50JztcbiAgICAgICAgICAgIHNjcmlwdC5hc3luYyA9IHRydWU7XG4gICAgICAgICAgICBzY3JpcHQuZGVmZXIgPSB0cnVlO1xuICAgICAgICAgICAgc2NyaXB0Lm9ubG9hZCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICBpZiAodHlwZW9mIGdvb2dsZSAhPT0gJ3VuZGVmaW5lZCcgJiYgZ29vZ2xlLmFjY291bnRzKSB7XG4gICAgICAgICAgICAgICAgICAgIGdvb2dsZS5hY2NvdW50cy5pZC5pbml0aWFsaXplKHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNsaWVudF9pZDogc2VsZi5nb29nbGVDbGllbnRJZCxcbiAgICAgICAgICAgICAgICAgICAgICAgIGNhbGxiYWNrOiBmdW5jdGlvbiAocmVzcG9uc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWxmLmhhbmRsZUdvb2dsZUF1dGgocmVzcG9uc2UuY3JlZGVudGlhbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAgICAgICAgIC8vIFJlbmRlciBhIGN1c3RvbSBidXR0b24gaGFuZGxlclxuICAgICAgICAgICAgICAgICAgICAkKCcjcGwtZ29vZ2xlLWJ0bicpLm9uKCdjbGljaycsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGdvb2dsZS5hY2NvdW50cy5pZC5wcm9tcHQoKTtcbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgICAgIGRvY3VtZW50LmhlYWQuYXBwZW5kQ2hpbGQoc2NyaXB0KTtcbiAgICAgICAgfSxcblxuICAgICAgICBoYW5kbGVHb29nbGVBdXRoOiBmdW5jdGlvbiAoY3JlZGVudGlhbCkge1xuICAgICAgICAgICAgdmFyIHNlbGYgPSB0aGlzO1xuXG4gICAgICAgICAgICB0aGlzLnNldExvYWRpbmcoJyNwbC1nb29nbGUtYnRuJywgdHJ1ZSk7XG5cbiAgICAgICAgICAgICQuYWpheCh7XG4gICAgICAgICAgICAgICAgdXJsOiB0aGlzLmFwaVVybCxcbiAgICAgICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgICAgICBkYXRhVHlwZTogJ2pzb24nLFxuICAgICAgICAgICAgICAgIGRhdGE6IHtcbiAgICAgICAgICAgICAgICAgICAgYWN0aW9uOiAnZ29vZ2xlQXV0aCcsXG4gICAgICAgICAgICAgICAgICAgIGNyZWRlbnRpYWw6IGNyZWRlbnRpYWwsXG4gICAgICAgICAgICAgICAgICAgIHRva2VuOiB0aGlzLmNzcmZUb2tlblxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24gKHJlc3BvbnNlKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5zdWNjZXNzKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2UubmVlZHNQcm9maWxlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5lbWFpbCA9IHJlc3BvbnNlLmVtYWlsIHx8ICcnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChyZXNwb25zZS5maXJzdE5hbWUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnI3BsLWZpcnN0bmFtZScpLnZhbChyZXNwb25zZS5maXJzdE5hbWUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAocmVzcG9uc2UubGFzdE5hbWUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJCgnI3BsLWxhc3RuYW1lJykudmFsKHJlc3BvbnNlLmxhc3ROYW1lKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZi5zaG93U3RlcCgncHJvZmlsZScpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWxmLnNob3dNZXNzYWdlKCdMb2dnZWQgaW4gc3VjY2Vzc2Z1bGx5IScsICdzdWNjZXNzJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LmxvY2F0aW9uLmhyZWYgPSBzZWxmLmJhY2tVcmwgfHwgcmVzcG9uc2UucmVkaXJlY3RVcmwgfHwgd2luZG93LmxvY2F0aW9uLm9yaWdpbjtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNlbGYuc2hvd01lc3NhZ2UocmVzcG9uc2UuZXJyb3IgfHwgJ0dvb2dsZSBhdXRoZW50aWNhdGlvbiBmYWlsZWQuJywgJ2Vycm9yJyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIGVycm9yOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICAgIHNlbGYuc2hvd01lc3NhZ2UoJ0FuIGVycm9yIG9jY3VycmVkLiBQbGVhc2UgdHJ5IGFnYWluLicsICdlcnJvcicpO1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgY29tcGxldGU6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgc2VsZi5zZXRMb2FkaW5nKCcjcGwtZ29vZ2xlLWJ0bicsIGZhbHNlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSxcblxuICAgICAgICAvLyA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyBDb3VudGRvd24gdGltZXJcbiAgICAgICAgLy8gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cblxuICAgICAgICBzdGFydENvdW50ZG93bjogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIHNlbGYgPSB0aGlzO1xuICAgICAgICAgICAgdmFyIHNlY29uZHMgPSB0aGlzLnJlc2VuZENvdW50ZG93bjtcbiAgICAgICAgICAgIHZhciAkYnRuID0gJCgnI3BsLXJlc2VuZC1idG4nKTtcbiAgICAgICAgICAgIHZhciAkY291bnRkb3duID0gJCgnI3BsLWNvdW50ZG93bicpO1xuICAgICAgICAgICAgdmFyICR3cmFwID0gJCgnI3BsLWNvdW50ZG93bi13cmFwJyk7XG5cbiAgICAgICAgICAgICRidG4ucHJvcCgnZGlzYWJsZWQnLCB0cnVlKTtcbiAgICAgICAgICAgICR3cmFwLnNob3coKTtcblxuICAgICAgICAgICAgaWYgKHRoaXMuY291bnRkb3duVGltZXIpIHtcbiAgICAgICAgICAgICAgICBjbGVhckludGVydmFsKHRoaXMuY291bnRkb3duVGltZXIpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB2YXIgdXBkYXRlRGlzcGxheSA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgbWlucyA9IE1hdGguZmxvb3Ioc2Vjb25kcyAvIDYwKTtcbiAgICAgICAgICAgICAgICB2YXIgc2VjcyA9IHNlY29uZHMgJSA2MDtcbiAgICAgICAgICAgICAgICAkY291bnRkb3duLnRleHQobWlucyArICc6JyArIChzZWNzIDwgMTAgPyAnMCcgOiAnJykgKyBzZWNzKTtcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIHVwZGF0ZURpc3BsYXkoKTtcblxuICAgICAgICAgICAgdGhpcy5jb3VudGRvd25UaW1lciA9IHNldEludGVydmFsKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICBzZWNvbmRzLS07XG4gICAgICAgICAgICAgICAgaWYgKHNlY29uZHMgPD0gMCkge1xuICAgICAgICAgICAgICAgICAgICBjbGVhckludGVydmFsKHNlbGYuY291bnRkb3duVGltZXIpO1xuICAgICAgICAgICAgICAgICAgICAkYnRuLnByb3AoJ2Rpc2FibGVkJywgZmFsc2UpO1xuICAgICAgICAgICAgICAgICAgICAkd3JhcC5oaWRlKCk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdXBkYXRlRGlzcGxheSgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sIDEwMDApO1xuICAgICAgICB9LFxuXG4gICAgICAgIC8vID09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG4gICAgICAgIC8vIFV0aWxpdGllc1xuICAgICAgICAvLyA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuXG4gICAgICAgIHNldExvYWRpbmc6IGZ1bmN0aW9uIChzZWxlY3RvciwgaXNMb2FkaW5nKSB7XG4gICAgICAgICAgICB2YXIgJGJ0biA9ICQoc2VsZWN0b3IpO1xuICAgICAgICAgICAgaWYgKGlzTG9hZGluZykge1xuICAgICAgICAgICAgICAgICRidG4ucHJvcCgnZGlzYWJsZWQnLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAkYnRuLmZpbmQoJy5wbC1idG5fX3RleHQnKS5oaWRlKCk7XG4gICAgICAgICAgICAgICAgJGJ0bi5maW5kKCcucGwtYnRuX19sb2FkZXInKS5zaG93KCk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICRidG4ucHJvcCgnZGlzYWJsZWQnLCBmYWxzZSk7XG4gICAgICAgICAgICAgICAgJGJ0bi5maW5kKCcucGwtYnRuX190ZXh0Jykuc2hvdygpO1xuICAgICAgICAgICAgICAgICRidG4uZmluZCgnLnBsLWJ0bl9fbG9hZGVyJykuaGlkZSgpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIHNob3dGaWVsZEVycm9yOiBmdW5jdGlvbiAoJGlucHV0KSB7XG4gICAgICAgICAgICAkaW5wdXQuYWRkQ2xhc3MoJ3BsLWlucHV0LS1lcnJvcicpO1xuICAgICAgICAgICAgJGlucHV0Lm9uZSgnaW5wdXQnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS5yZW1vdmVDbGFzcygncGwtaW5wdXQtLWVycm9yJyk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICRpbnB1dC5mb2N1cygpO1xuICAgICAgICB9LFxuXG4gICAgICAgIHNob3dNZXNzYWdlOiBmdW5jdGlvbiAodGV4dCwgdHlwZSkge1xuICAgICAgICAgICAgdmFyICRtc2cgPSAkKCcjcGwtbWVzc2FnZScpO1xuICAgICAgICAgICAgJG1zZy50ZXh0KHRleHQpXG4gICAgICAgICAgICAgICAgLnJlbW92ZUNsYXNzKCdwbC1tZXNzYWdlLS1lcnJvciBwbC1tZXNzYWdlLS1zdWNjZXNzJylcbiAgICAgICAgICAgICAgICAuYWRkQ2xhc3MoJ3BsLW1lc3NhZ2UtLScgKyB0eXBlKVxuICAgICAgICAgICAgICAgIC5zaG93KCk7XG5cbiAgICAgICAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICRtc2cuZmFkZU91dCgyMDApO1xuICAgICAgICAgICAgfSwgNDAwMCk7XG4gICAgICAgIH0sXG5cbiAgICAgICAgaXNWYWxpZEVtYWlsOiBmdW5jdGlvbiAoZW1haWwpIHtcbiAgICAgICAgICAgIHJldHVybiAvXlteXFxzQF0rQFteXFxzQF0rXFwuW15cXHNAXSskLy50ZXN0KGVtYWlsKTtcbiAgICAgICAgfSxcblxuICAgICAgICBlc2NhcGVIdG1sOiBmdW5jdGlvbiAoc3RyKSB7XG4gICAgICAgICAgICB2YXIgZGl2ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICAgICAgICBkaXYuYXBwZW5kQ2hpbGQoZG9jdW1lbnQuY3JlYXRlVGV4dE5vZGUoc3RyKSk7XG4gICAgICAgICAgICByZXR1cm4gZGl2LmlubmVySFRNTDtcbiAgICAgICAgfVxuICAgIH07XG5cbiAgICAkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgICAgIFBhc3N3b3JkbGVzc0xvZ2luLmluaXQoKTtcbiAgICB9KTtcblxuICAgIHdpbmRvdy5QYXNzd29yZGxlc3NMb2dpbiA9IFBhc3N3b3JkbGVzc0xvZ2luO1xuXG59KShqUXVlcnkpO1xuXG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiXSwibmFtZXMiOlsiJCIsIlBhc3N3b3JkbGVzc0xvZ2luIiwiYXBpVXJsIiwiY3NyZlRva2VuIiwiZ29vZ2xlRW5hYmxlZCIsImdvb2dsZUNsaWVudElkIiwicmVzZW5kQ291bnRkb3duIiwiYmFja1VybCIsImVtYWlsIiwiY3VycmVudFN0ZXAiLCJjb3VudGRvd25UaW1lciIsImxvYWRpbmciLCJpbml0IiwiJGNvbnRhaW5lciIsImxlbmd0aCIsImRhdGEiLCJwYXJzZUludCIsImJpbmRFdmVudHMiLCJpbml0R29vZ2xlU2lnbkluIiwic2VsZiIsIndpbmRvdyIsIm9uIiwiZSIsInN0YXRlIiwib3JpZ2luYWxFdmVudCIsInN0ZXAiLCJzaG93U3RlcCIsImhpc3RvcnkiLCJyZXBsYWNlU3RhdGUiLCJsb2NhdGlvbiIsImhyZWYiLCJwcmV2ZW50RGVmYXVsdCIsInNlbmRDb2RlIiwiZG9jdW1lbnQiLCJoYW5kbGVDb2RlSW5wdXQiLCJoYW5kbGVDb2RlS2V5ZG93biIsInBhc3RlZERhdGEiLCJjbGlwYm9hcmREYXRhIiwiZ2V0RGF0YSIsImhhbmRsZUNvZGVQYXN0ZSIsImNvbXBsZXRlUHJvZmlsZSIsInByb3AiLCJmcm9tUG9wc3RhdGUiLCJoaWRlIiwiZmFkZUluIiwicHVzaFN0YXRlIiwic2V0VGltZW91dCIsImZvY3VzIiwiZmlyc3QiLCJ0cmltIiwidmFsIiwiaXNWYWxpZEVtYWlsIiwic2hvd0ZpZWxkRXJyb3IiLCJzZXRMb2FkaW5nIiwiYWpheCIsInVybCIsIm1ldGhvZCIsImRhdGFUeXBlIiwiYWN0aW9uIiwidG9rZW4iLCJzdWNjZXNzIiwicmVzcG9uc2UiLCJodG1sIiwiY2xvc2VzdCIsImZpbmQiLCJ0ZXh0IiwiJHN1YnRpdGxlIiwiZXNjYXBlSHRtbCIsImNsZWFyQ29kZUlucHV0cyIsInN0YXJ0Q291bnRkb3duIiwic2hvd01lc3NhZ2UiLCJlcnJvciIsImNvbXBsZXRlIiwiJGlucHV0IiwicmVwbGFjZSIsImFkZENsYXNzIiwicmVtb3ZlQ2xhc3MiLCIkbmV4dCIsImNoZWNrQ29kZUNvbXBsZXRlIiwia2V5IiwiJHByZXYiLCIkcHJldkFycm93IiwiJG5leHRBcnJvdyIsImRpZ2l0cyIsInN1YnN0cmluZyIsIiRpbnB1dHMiLCJpIiwiZXEiLCJjb2RlIiwiYWxsRmlsbGVkIiwiZWFjaCIsInZlcmlmeUNvZGUiLCJuZWVkc1Byb2ZpbGUiLCJmaXJzdE5hbWUiLCJsYXN0TmFtZSIsInJlZGlyZWN0VXJsIiwib3JpZ2luIiwic2hvd0NvZGVFcnJvciIsIm1lc3NhZ2UiLCIkZXJyb3IiLCJzaG93IiwiZmlyc3RuYW1lIiwibGFzdG5hbWUiLCJzY3JpcHQiLCJjcmVhdGVFbGVtZW50Iiwic3JjIiwiYXN5bmMiLCJkZWZlciIsIm9ubG9hZCIsImdvb2dsZSIsImFjY291bnRzIiwiaWQiLCJpbml0aWFsaXplIiwiY2xpZW50X2lkIiwiY2FsbGJhY2siLCJoYW5kbGVHb29nbGVBdXRoIiwiY3JlZGVudGlhbCIsInByb21wdCIsImhlYWQiLCJhcHBlbmRDaGlsZCIsInNlY29uZHMiLCIkYnRuIiwiJGNvdW50ZG93biIsIiR3cmFwIiwiY2xlYXJJbnRlcnZhbCIsInVwZGF0ZURpc3BsYXkiLCJtaW5zIiwiTWF0aCIsImZsb29yIiwic2VjcyIsInNldEludGVydmFsIiwic2VsZWN0b3IiLCJpc0xvYWRpbmciLCJvbmUiLCJ0eXBlIiwiJG1zZyIsImZhZGVPdXQiLCJ0ZXN0Iiwic3RyIiwiZGl2IiwiY3JlYXRlVGV4dE5vZGUiLCJpbm5lckhUTUwiLCJyZWFkeSIsImpRdWVyeSJdLCJzb3VyY2VSb290IjoiIn0=