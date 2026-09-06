(function () {
    'use strict';
    function drawerFor(button) {
        return document.getElementById(button.getAttribute('data-contextual-help-open'));
    }
    document.addEventListener('click', function (event) {
        var open = event.target.closest('[data-contextual-help-open]');
        if (open) {
            var drawer = drawerFor(open);
            if (!drawer) { return; }
            drawer.removeAttribute('inert');
            drawer.setAttribute('aria-hidden', 'false');
            open.setAttribute('aria-expanded', 'true');
            drawer._contextualHelpOpener = open;
            drawer.focus();
            return;
        }
        var close = event.target.closest('[data-contextual-help-close]');
        if (!close) { return; }
        var panel = close.closest('.chisimba-contextual-help-drawer');
        if (!panel) { return; }
        panel.setAttribute('aria-hidden', 'true');
        panel.setAttribute('inert', '');
        if (panel._contextualHelpOpener) {
            panel._contextualHelpOpener.setAttribute('aria-expanded', 'false');
            panel._contextualHelpOpener.focus();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        var panel = document.querySelector('.chisimba-contextual-help-drawer[aria-hidden="false"]');
        if (panel) {
            panel.querySelector('[data-contextual-help-close]').click();
        }
    });
}());
