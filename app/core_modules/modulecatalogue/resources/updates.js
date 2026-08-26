(function () {
    'use strict';

    function postUpdate(url, values) {
        const body = new URLSearchParams(values);
        body.set('ajax', '1');
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        }).then(async function (response) {
            let payload;
            try {
                payload = await response.json();
            } catch (error) {
                payload = {ok: false};
            }
            if (!response.ok || !payload.ok) {
                const failure = new Error(payload.message || '');
                failure.payload = payload;
                throw failure;
            }
            return payload;
        });
    }

    function showEmptyState(root) {
        if (root.querySelector('[data-update-item]')) {
            const all = root.querySelector('[data-update-all]');
            if (all && root.querySelectorAll('[data-update-item]').length < 2) {
                all.closest('.module-updates__all').remove();
            }
            return;
        }
        root.innerHTML = '';
        const message = document.createElement('p');
        message.className = 'module-updates__empty';
        message.textContent = root.dataset.emptyMessage || '';
        root.appendChild(message);
    }

    document.addEventListener('click', function (event) {
        const moduleButton = event.target.closest('[data-update-module]');
        const allButton = event.target.closest('[data-update-all]');
        if (!moduleButton && !allButton) {
            return;
        }
        const button = moduleButton || allButton;
        const root = button.closest('[data-module-updates]');
        const item = moduleButton ? button.closest('[data-update-item]') : null;
        const status = moduleButton
            ? item.querySelector('[data-update-status]')
            : root.querySelector('[data-update-all-status]');
        const values = {csrf_token: button.dataset.csrf || ''};
        if (moduleButton) {
            values.mod = button.dataset.module || '';
            values.patchver = button.dataset.version || '';
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        status.className = 'module-updates__status';
        status.textContent = moduleButton
            ? root.dataset.updatingMessage
            : root.dataset.applyingMessage;

        postUpdate(button.dataset.url, values).then(function (payload) {
            status.classList.add('is-success');
            status.textContent = payload.message;
            if (moduleButton) {
                item.classList.add('is-complete');
                window.setTimeout(function () {
                    item.remove();
                    showEmptyState(root);
                }, 1400);
            } else {
                root.querySelectorAll('[data-update-item]').forEach(function (updateItem) {
                    updateItem.remove();
                });
                window.setTimeout(function () { showEmptyState(root); }, 1400);
            }
        }).catch(function (error) {
            const payload = error.payload || {};
            if (allButton && Array.isArray(payload.updatedModules)) {
                payload.updatedModules.forEach(function (moduleId) {
                    const completed = root.querySelector('[data-update-module][data-module="' + CSS.escape(moduleId) + '"]');
                    if (completed) {
                        completed.closest('[data-update-item]').remove();
                    }
                });
                showEmptyState(root);
            }
            if (payload.csrfToken) {
                button.dataset.csrf = payload.csrfToken;
            }
            status.classList.add('is-error');
            status.textContent = payload.message || root.dataset.requestFailedMessage || '';
            button.disabled = false;
        }).finally(function () {
            button.removeAttribute('aria-busy');
        });
    });
}());
