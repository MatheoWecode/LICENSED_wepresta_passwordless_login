/**
 * Passwordless Login - Front-office JavaScript
 * jQuery-based multi-step authentication flow
 */

import '../scss/front.scss';

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
            history.replaceState({ step: 'email' }, '', window.location.href);
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
                history.pushState({ step: step }, '', window.location.href);
            }

            // Focus management
            if (step === 'email') {
                setTimeout(function () { $('#pl-email').focus(); }, 250);
            } else if (step === 'code') {
                setTimeout(function () { $('.pl-code-digit').first().focus(); }, 250);
            } else if (step === 'profile') {
                setTimeout(function () { $('#pl-firstname').focus(); }, 250);
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
                        $('#pl-code-subtitle').html(
                            $('#pl-code-subtitle').closest('.pl-card').find('.pl-title').text() ?
                            self.email : self.email
                        );
                        // Set subtitle with the email
                        var $subtitle = $('#pl-code-subtitle');
                        $subtitle.html($subtitle.data('template') || ('Code sent to <strong>' + self.escapeHtml(self.email) + '</strong>'));
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
                        setTimeout(function () { $('.pl-code-digit').first().focus(); }, 100);
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
            $msg.text(text)
                .removeClass('pl-message--error pl-message--success')
                .addClass('pl-message--' + type)
                .show();

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

