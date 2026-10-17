/* Shared native row deletion. No automatic retries of uncertain writes. */
(() => {
    'use strict';
    if (document.documentElement.dataset.rowDeleteReady) return;
    document.documentElement.dataset.rowDeleteReady = 'true';
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-row-delete] button');
        if (!button || button.disabled) return;
        const row = button.closest('tr');
        const list = button.closest('[data-row-delete-list]');
        if (!row || !list) return;
        const error = button.closest('[data-row-delete]').querySelector('[data-row-delete-error]');
        const status = list.querySelector('[data-row-delete-status]');
        if (!window.confirm(button.dataset.confirm)) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        error.textContent = '';
        status.textContent = '';
        try {
            const url = new URL(button.dataset.url, window.location.href);
            if (url.origin !== window.location.origin) throw new Error('Invalid destination');
            const response = await fetch(url.href, {
                method: 'POST', credentials: 'same-origin', redirect: 'error',
                headers: {'Accept': 'text/plain'},
                body: new URLSearchParams({id: button.dataset.id, revision: button.dataset.revision, csrf_token: button.dataset.csrf})
            });
            const text = await response.text();
            if (!response.ok || text !== 'RECORD_DELETED') throw new Error('Unconfirmed deletion');
            row.remove();
            status.textContent = button.dataset.success;
            list.focus();
        } catch (_) {
            // Do not expose login HTML/server diagnostics, or pretend an uncertain write failed safely.
            error.textContent = button.dataset.failure;
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    });
})();
