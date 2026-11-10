/* Native own-profile forms. Requests never discard the selected file or typed work on failure. */
(function () {
    'use strict';
    function start() {
        var root = document.querySelector('[data-profile-workspace]');
        if (!root) return;
        var text = root.dataset;
        var busy = false;
        document.querySelectorAll('form[data-profile-action]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (busy) return;
                busy = true;
                var buttons = Array.from(document.querySelectorAll('form[data-profile-action] button[type="submit"]'));
                buttons.forEach(function (button) { button.disabled = true; });
                var status = form.querySelector('[data-profile-status]');
                status.textContent = text.saving;
                form.setAttribute('aria-busy', 'true');
                try {
                    var response = await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'Accept': 'application/json'}});
                    var data = await response.json();
                    if (data.csrf_token) form.querySelector('[name="csrf_token"]').value = data.csrf_token;
                    status.textContent = data.message || text.failed;
                    status.className = data.ok ? 'success' : 'error';
                    if (data.ok && typeof data.image === 'string') {
                        document.querySelectorAll('.toolbar_userimage, #profile-photo .chisimba-biography-portrait').forEach(function (element) { element.innerHTML = data.image; });
                    }
                } catch (error) {
                    // The server may have completed a request whose response was lost.
                    // Do not replay it or clear inputs; tell the user to check first.
                    status.textContent = text.uncertain;
                    status.className = 'error';
                } finally {
                    busy = false;
                    form.removeAttribute('aria-busy');
                    buttons.forEach(function (button) { button.disabled = false; });
                }
            });
        });
        var school = document.getElementById('input_school');
        var schoolId = document.getElementById('input_school_id');
        var results = document.getElementById('profile-school-results');
        if (!school || !schoolId || !results) return;
        school.setAttribute('autocomplete', 'off');
        school.setAttribute('aria-controls', results.id);
        var timer, request, generation = 0;
        school.addEventListener('input', function () {
            schoolId.value = '';
            results.replaceChildren();
            clearTimeout(timer);
            if (request) request.abort();
            var version = ++generation;
            if (school.value.trim().length < 3) return;
            timer = setTimeout(async function () {
                request = new AbortController();
                var url = new URL(root.dataset.schoolUrl, location.href);
                url.searchParams.set('term', school.value.trim());
                try {
                    var response = await fetch(url, {signal: request.signal, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
                    if (!response.ok) throw new Error('lookup');
                    var rows = await response.json();
                    if (version !== generation) return;
                    results.replaceChildren();
                    if (!rows.length) { results.textContent = text.noSchools; return; }
                    rows.forEach(function (row) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = row.label;
                        button.addEventListener('click', function () {
                            school.value = row.label;
                            schoolId.value = row.value;
                            results.replaceChildren();
                            school.focus();
                        });
                        results.append(button);
                    });
                } catch (error) {
                    if (error.name !== 'AbortError' && version === generation) results.textContent = text.failed;
                }
            }, 250);
        });
        school.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                ++generation; clearTimeout(timer); if (request) request.abort(); results.replaceChildren();
            }
        });
        school.form.addEventListener('reset', function () {
            ++generation; clearTimeout(timer); if (request) request.abort(); results.replaceChildren();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}());
