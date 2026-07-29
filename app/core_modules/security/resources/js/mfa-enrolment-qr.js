(function () {
    'use strict';

    function renderQr(container) {
        var uri = container.getAttribute('data-otpauth-uri');
        if (!uri || uri.indexOf('otpauth://totp/') !== 0
            || typeof window.qrcode !== 'function') {
            container.setAttribute('data-qr-status', 'unavailable');
            return;
        }

        try {
            var code = window.qrcode(0, 'M');
            code.addData(uri, 'Byte');
            code.make();
            container.innerHTML = code.createSvgTag({
                cellSize: 5,
                margin: 4,
                scalable: true
            });
            container.setAttribute('data-qr-status', 'ready');
            var svg = container.querySelector('svg');
            if (svg) {
                svg.setAttribute('role', 'img');
                svg.setAttribute('aria-label', container.getAttribute(
                    'data-qr-label'
                ) || 'Authenticator setup QR code');
            }
        } catch (error) {
            container.textContent = '';
            container.setAttribute('data-qr-status', 'unavailable');
        }
    }

    function initialise() {
        var containers = document.querySelectorAll('.chisimba-mfa-qr');
        for (var i = 0; i < containers.length; i += 1) {
            renderQr(containers[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise);
    } else {
        initialise();
    }
}());
