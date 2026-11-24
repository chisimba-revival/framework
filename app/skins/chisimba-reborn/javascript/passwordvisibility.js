/* Shared progressive enhancement: passwords stay masked unless explicitly revealed. */
(function () {
    'use strict';
    var config = document.getElementById('chisimba-password-options');
    if (!config) return;
    var labels = JSON.parse(config.textContent);
    var sequence = 0;
    function enhance(root) {
        root.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.dataset.passwordVisibilityReady || input.closest('[data-password-visibility="off"]')) return;
            input.dataset.passwordVisibilityReady = 'true';
            if (!input.id) {
                do { input.id = 'chisimba-password-' + (++sequence); }
                while (document.getElementById(input.id) !== input);
            }
            var wrapper = document.createElement('span');
            wrapper.className = 'chisimba-password-field';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'chisimba-password-toggle';
            button.setAttribute('aria-controls', input.id);
            wrapper.appendChild(button);
            function display(visible) {
                input.type = visible ? 'text' : 'password';
                button.setAttribute('aria-label', visible ? labels.hide : labels.show);
                button.title = visible ? labels.hide : labels.show;
                button.setAttribute('aria-pressed', String(visible));
                button.innerHTML = visible ? labels.hideIcon : labels.showIcon;
            }
            display(false);
            button.addEventListener('click', function () { display(input.type === 'password'); });
            wrapper.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') display(false);
            });
            if (input.form) {
                input.form.addEventListener('submit', function () { display(false); });
                input.form.addEventListener('reset', function () { display(false); });
            }
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) display(false);
            });
        });
    }
    enhance(document);
    new MutationObserver(function (records) {
        if (records.some(function (record) { return record.addedNodes.length; })) enhance(document);
    }).observe(document.body, { childList: true, subtree: true });
}());
