/* Renew logout protection without abandoning an open editor on preflight failure. */
(function () {
    'use strict';
    if (window.ChisimbaLogoutRecovery) return;
    window.ChisimbaLogoutRecovery = true;
    document.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!form.matches('[data-logout-recovery]')) return;
        event.preventDefault();
        if (form.dataset.busy) return;
        form.dataset.busy = 'true';
        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            var response = await fetch(form.dataset.tokenUrl, {
                method: 'POST', credentials: 'same-origin', signal: AbortSignal.timeout(30000),
                headers: {'X-Chisimba-Form': 'security', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: new URLSearchParams({actor: form.elements.native_auth_actor.value}).toString()
            });
            if (response.redirected || !response.ok || !(response.headers.get('Content-Type') || '').includes('application/json')) throw new Error('invalid');
            var result = await response.json();
            if (!result.token) throw new Error('missing-token');
            form.elements.native_auth_logout.value = result.token;
            HTMLFormElement.prototype.submit.call(form);
        } catch (_) {
            var notice = form.querySelector('[role="alert"]');
            if (!notice) { notice = document.createElement('span'); notice.setAttribute('role','alert'); form.appendChild(notice); }
            notice.textContent = form.dataset.recoveryError;
        } finally { button.disabled = false; delete form.dataset.busy; }
    });
}());
