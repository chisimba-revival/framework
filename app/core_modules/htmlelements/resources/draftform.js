/* Opt-in, tab-local draft recovery and explicit JSON saves through shared editor APIs.
 * Never stores CSRF tokens, retries uncertain writes or overwrites a newer revision.
 */
(function () {
    'use strict';
    document.querySelectorAll('form[data-draft-form]').forEach(form => {
        const key = 'chisimba-draft:' + form.dataset.draftForm;
        const names = form.dataset.draftFields.split(',');
        const status = form.querySelector('[data-draft-status]');
        const recovery = form.querySelector('[data-draft-recovery]');
        const controls = () => Array.from(form.elements).filter(el => names.includes(el.name));
        let candidate = null, busy = false, last = '';
        function message(text) { status.textContent = text; }
        function snapshot() {
            const data = {};
            controls().forEach(el => {
                if (el.tagName === 'TEXTAREA' && window.ChisimbaEditor) window.ChisimbaEditor.sync(el.id);
                if (el.multiple) data[el.name] = Array.from(el.selectedOptions, o => o.value);
                else data[el.name] = el.value;
            });
            return JSON.stringify(data);
        }
        try { candidate = JSON.parse(sessionStorage.getItem(key) || 'null'); }
        catch (_) { message(form.dataset.draftUnavailable); }
        if (candidate && typeof candidate === 'object' && !Array.isArray(candidate)) recovery.hidden = false;
        last = snapshot();
        function preserve() {
            const value = snapshot();
            if (value === last) return;
            last = value;
            try { sessionStorage.setItem(key, value); if (!busy) message(form.dataset.draftStored); }
            catch (_) { message(form.dataset.draftUnavailable); }
        }
        form.querySelector('[data-draft-restore]').addEventListener('click', () => {
            if (!candidate) return;
            controls().forEach(el => {
                const value = candidate[el.name];
                if (el.multiple && Array.isArray(value)) Array.from(el.options).forEach(o => { o.selected = value.includes(o.value); });
                else if (typeof value === 'string') {
                    el.value = value;
                    if (el.tagName === 'TEXTAREA' && window.ChisimbaEditor) window.ChisimbaEditor.setData(el.id, value);
                }
            });
            recovery.hidden = true; preserve(); message(form.dataset.draftStored);
        });
        form.querySelector('[data-draft-discard]').addEventListener('click', () => {
            try { sessionStorage.removeItem(key); } catch (_) {}
            candidate = null; recovery.hidden = true;
        });
        form.addEventListener('input', preserve);
        form.addEventListener('change', preserve);
        // Editors may own an iframe; poll only this small opt-in form for their changes.
        const timer = setInterval(preserve, 1000);
        window.addEventListener('pagehide', () => { preserve(); clearInterval(timer); });
        form.addEventListener('submit', async event => {
            event.preventDefault(); if (busy || !form.reportValidity()) return;
            preserve();
            // Always keep a local copy before a potentially uncertain write.
            try { sessionStorage.setItem(key, snapshot()); } catch (_) {}
            busy = true; message(form.dataset.draftSaving);
            const buttons = form.querySelectorAll('button[type="submit"]');
            buttons.forEach(b => { b.disabled = true; }); form.setAttribute('aria-busy','true');
            let failure = form.dataset.draftFailure;
            try {
                const tokenResponse = await fetch(form.dataset.tokenUrl, {credentials:'same-origin', headers:{Accept:'application/json'}, cache:'no-store'});
                const token = await tokenResponse.json();
                if (!tokenResponse.ok || !token.token) { if (typeof token.message === 'string') failure = token.message; throw new Error('token'); }
                const data = new FormData(form); data.set('csrf_token', token.token);
                const response = await fetch(form.action, {method:'POST', body:data, credentials:'same-origin', headers:{Accept:'application/json'}});
                const result = await response.json();
                if (!response.ok || !result.ok) { if (typeof result.message === 'string') failure = result.message; throw new Error('save'); }
                try { sessionStorage.removeItem(key); } catch (_) {}
                last = snapshot(); clearInterval(timer);
                message(result.message); window.location.assign(result.url);
            } catch (_) { message(failure); }
            finally {busy = false; buttons.forEach(b => {b.disabled = false;}); form.removeAttribute('aria-busy');}
        });
    });
}());
