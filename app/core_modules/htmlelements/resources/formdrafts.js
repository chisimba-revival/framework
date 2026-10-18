/** Opt-in, tab-local draft recovery. Consumers supply identity/scope keys and safe fields. */
(function (global) {
    'use strict';
    var active = [];
    function attach(form, options) {
        var fields = options.fields.map(function (name) { return form.elements.namedItem(name); }).filter(Boolean);
        var key = 'chisimba:form-draft:v1:' + options.key;
        var baseline = snapshot(), pending = null, storage;
        var notice = document.createElement('div');
        notice.className = 'chisimba-notice';
        notice.setAttribute('role', 'status');
        form.appendChild(notice);
        function snapshot() { return JSON.stringify(fields.map(function (field) { return [field.name, field.value]; })); }
        function say(message) { notice.textContent = message; notice.hidden = !message; }
        function remove() { try { storage.removeItem(key); } catch (_) {} }
        function persist() {
            var value = snapshot();
            if (value === baseline) { if (!pending) remove(); return; }
            pending = null;
            try {
                storage.setItem(key, JSON.stringify({baseline: baseline, value: value}));
                say(options.messages.kept);
            } catch (_) { say(options.messages.unavailable); }
        }
        function restore(record) {
            var values = JSON.parse(record.value);
            fields.forEach(function (field) {
                var value = values.find(function (entry) { return entry[0] === field.name; });
                if (value && typeof value[1] === 'string') field.value = value[1];
            });
            pending = null;
            persist();
            say(options.messages.restored);
            for (var parent = form.parentElement; parent; parent = parent.parentElement) {
                if (parent.tagName === 'DETAILS') parent.open = true;
            }
            if (options.onRestore) options.onRestore();
        }
        try {
            storage = global.sessionStorage;
            var record = JSON.parse(storage.getItem(key) || 'null');
            if (record && typeof record.baseline === 'string' && typeof record.value === 'string' && Array.isArray(JSON.parse(record.value))) {
                if (record.value === baseline) remove(); // Server already contains the draft.
                else if (record.baseline === baseline) restore(record);
                else {
                    pending = record;
                    say(options.messages.conflict);
                    [[options.messages.restore, function () { restore(record); }],
                     [options.messages.discard, function () { pending = null; remove(); say(''); }]].forEach(function (item) {
                        var button = document.createElement('button');
                        button.type = 'button'; button.className = 'button chisimba-button-secondary';
                        button.textContent = item[0]; button.addEventListener('click', item[1]); notice.appendChild(button);
                    });
                }
            }
        } catch (_) { say(options.messages.unavailable); }
        if (!notice.textContent) notice.hidden = true;
        form.addEventListener('input', persist);
        form.addEventListener('change', persist);
        var api = {
            snapshot: snapshot,
            persist: persist,
            dirty: function () { return Boolean(pending) || snapshot() !== baseline; },
            saved: function (submitted) {
                // Do not erase text typed while the request was in flight.
                baseline = submitted; pending = null;
                if (snapshot() === submitted) { remove(); say(''); } else persist();
            },
            reset: function () { baseline = snapshot(); pending = null; remove(); say(''); }
        };
        active.push(api);
        return api;
    }
    global.addEventListener('beforeunload', function (event) {
        if (active.some(function (draft) { return draft.dirty(); })) {
            event.preventDefault(); event.returnValue = '';
        }
    });
    global.ChisimbaFormDrafts = {attach: attach};
}(window));
