/* Shared expanding text fields. Native resizing remains available without JS. */
(function () {
    'use strict';
    function initialise() {
        document.querySelectorAll('textarea[data-auto-expand]').forEach(function (field) {
            function fit() {
                if (!field.getClientRects().length) return;
                var style = getComputedStyle(field);
                var border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
                field.style.height = 'auto';
                field.style.height = Math.ceil(field.scrollHeight + border) + 'px';
            }
            field.addEventListener('input', fit);
            if (field.form) field.form.addEventListener('reset', function () {
                requestAnimationFrame(fit);
            });
            if (window.ResizeObserver) {
                var previousWidth = -1;
                new ResizeObserver(function (entries) {
                    var width = entries[0].contentRect.width;
                    if (width !== previousWidth) {
                        previousWidth = width;
                        fit();
                    }
                }).observe(field);
            } else {
                window.addEventListener('resize', fit);
            }
            if (document.fonts) document.fonts.ready.then(fit);
            fit();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }
}());
