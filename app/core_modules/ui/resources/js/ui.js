/**
 * Chisimba native UI behaviour.
 *
 * Provides delegated open and close handling for native dialog components.
 *
 * Author/Developer: Derek Keats
 */

(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var openTrigger = event.target.closest('[data-ui-open]');
        var closeTrigger = event.target.closest('[data-ui-close]');
        var dialog;
        var id;

        if (openTrigger) {
            id = openTrigger.getAttribute('data-ui-open');
            dialog = document.getElementById(id);

            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }

            return;
        }

        if (closeTrigger) {
            id = closeTrigger.getAttribute('data-ui-close');
            dialog = document.getElementById(id);

            if (dialog && typeof dialog.close === 'function') {
                dialog.close();
            }
        }
    });
}());
