/**
 * Module Starter - JavaScript Back-Office
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('[Wepresta_Passwordless_Login] Admin JS loaded');

        // Confirmation avant suppression
        $('[data-wepresta_passwordless_login-confirm]').on('click', function(e) {
            var message = $(this).data('wepresta_passwordless_login-confirm') || 'Êtes-vous sûr ?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Toggle des options conditionnelles
        $('[data-wepresta_passwordless_login-toggle]').on('change', function() {
            var target = $(this).data('wepresta_passwordless_login-toggle');
            var $target = $(target);

            if ($(this).is(':checked')) {
                $target.slideDown(200);
            } else {
                $target.slideUp(200);
            }
        }).trigger('change');
    });

})(jQuery);

