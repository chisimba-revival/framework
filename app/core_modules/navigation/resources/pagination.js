/* Shared native pagination. Failed and superseded requests never replace results. */
(function () {
    'use strict';
    function initialise(root) {
        if (root.dataset.paginationReady) return;
        root.dataset.paginationReady = 'true';
        const results = root.querySelector('[data-pagination-results]');
        const status = root.querySelector('[data-pagination-status]');
        const links = root.querySelector('[data-pagination-links]');
        const count = Number(root.dataset.pages);
        let sequence = 0, request;
        function urlFor(page) {
            const url = new URL(root.dataset.url, location.href);
            if (url.origin !== location.origin) throw new Error('Invalid pagination origin');
            url.searchParams.set('page', String(page));
            return url;
        }
        function renderLinks(page) {
            const pages = new Set([0, count - 1]);
            for (let n = Math.max(0, page - 4); n <= Math.min(count - 1, page + 4); n++) pages.add(n);
            links.replaceChildren();
            Array.from(pages).sort((a, b) => a - b).forEach(n => {
                const a = document.createElement('a');
                a.className = 'button';
                a.href = urlFor(n).href;
                a.dataset.page = String(n);
                a.textContent = String(n + 1);
                a.setAttribute('aria-controls', results.id);
                if (n === page) a.setAttribute('aria-current', 'page');
                links.append(a);
            });
        }
        async function load(page, focus) {
            const version = ++sequence;
            if (request) request.abort();
            request = new AbortController();
            results.setAttribute('aria-busy', 'true');
            status.textContent = root.dataset.loading;
            try {
                const response = await fetch(urlFor(page), {signal: request.signal, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok || response.redirected) throw new Error('Page unavailable');
                const html = await response.text();
                if (version !== sequence) return;
                results.innerHTML = html;
                root.dataset.page = String(page);
                renderLinks(page);
                if (focus) links.querySelector('[aria-current]').focus({preventScroll: true});
                status.textContent = '';
            } catch (error) {
                if (version !== sequence || error.name === 'AbortError') return;
                status.textContent = root.dataset.error;
            } finally {
                if (version === sequence) results.setAttribute('aria-busy', 'false');
            }
        }
        links.addEventListener('click', event => {
            const link = event.target.closest('a[data-page]');
            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            load(Number(link.dataset.page), true);
        });
        load(Number(root.dataset.page), false);
    }
    function ready() { document.querySelectorAll('[data-pagination]').forEach(initialise); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();
}());
