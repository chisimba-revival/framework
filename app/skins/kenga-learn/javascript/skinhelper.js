/**
 * Chisimba Reborn canvas helper.
 *
 * The initial architectural proof intentionally requires no JavaScript for
 * layout. CSS Grid performs region placement and responsive reflow, allowing
 * the page to remain usable when JavaScript is unavailable.
 *
 * This file is retained as the documented extension point for the accessible
 * mobile drawer behaviour planned for the next iteration.
 *
 * @author Derek Keats
 * @category Chisimba
 * @package canvases
 * @license GNU GPL version 2 or later
 */

(function () {
    'use strict';

    /**
     * Record that the modern canvas helper has loaded.
     *
     * A class on the root element will allow later progressive enhancements to
     * distinguish between the resilient CSS-only baseline and JavaScript-
     * enhanced behaviour without hiding content prematurely.
     */
    document.documentElement.classList.add('chisimba-reborn-js');
}());

/* BEGIN CHISIMBA-REBORN MOBILE NAV
 *
 * Progressive enhancement for the existing #menuList navigation.
 * No menu markup is duplicated and no PHP menu-generation code is changed.
 */
(function () {
    'use strict';

    function initialiseChisimbaMobileNavigation() {
        var menu = document.getElementById('menuList');

        if (!menu || menu.dataset.chisimbaMobileNavInitialised === 'true') {
            return;
        }

        var container = menu.parentElement;
        if (!container) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'chisimba-menu-toggle';
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', 'menuList');
        button.setAttribute('aria-label', 'Open navigation menu');
        button.innerHTML =
            '<svg class="chisimba-menu-toggle__icon" ' +
            'viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
            '<path d="M4 7h16M4 12h16M4 17h16"></path>' +
            '</svg>';

        container.insertBefore(button, menu);
        menu.dataset.chisimbaMobileNavInitialised = 'true';

        button.addEventListener('click', function () {
            var open = container.classList.toggle('chisimba-mobile-nav-open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                open ? 'Close navigation menu' : 'Open navigation menu'
            );
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' &&
                container.classList.contains('chisimba-mobile-nav-open')) {
                container.classList.remove('chisimba-mobile-nav-open');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-label', 'Open navigation menu');
                button.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                container.classList.remove('chisimba-mobile-nav-open');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-label', 'Open navigation menu');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initialiseChisimbaMobileNavigation
        );
    } else {
        initialiseChisimbaMobileNavigation();
    }
}());
/* END CHISIMBA-REBORN MOBILE NAV */

