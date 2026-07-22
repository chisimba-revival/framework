(function () {
    'use strict';
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-chisimba-message-dismiss]');
        if (!button) return;
        var message = button.closest('[data-chisimba-message]');
        if (!message) return;
        message.dispatchEvent(new CustomEvent('chisimba:message-dismiss', {bubbles: true}));
        message.remove();
    });
}());
