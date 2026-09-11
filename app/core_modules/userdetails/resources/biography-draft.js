/** Keep an unsaved biography in this tab while its owner changes their photo. */
(() => {
    'use strict';
    const form = document.getElementById('author-biography-form');
    if (!form) return;
    const key = 'chisimba:biography-draft:' + location.pathname + ':' + form.dataset.draftUser;
    const fields = [...form.elements].filter(field => field.name === 'biography' || field.name.startsWith('links['));
    try {
        if (form.dataset.saved === 'true') sessionStorage.removeItem(key);
        const draft = JSON.parse(sessionStorage.getItem(key) || 'null');
        if (draft && Date.now() - draft.savedAt < 3600000) {
            fields.forEach(field => { if (typeof draft.values[field.name] === 'string') field.value = draft.values[field.name]; });
        } else if (draft) {
            sessionStorage.removeItem(key);
        }
        form.addEventListener('input', () => {
            try { sessionStorage.setItem(key, JSON.stringify({savedAt: Date.now(), values: Object.fromEntries(fields.map(field => [field.name, field.value]))})); } catch (_) { /* Form remains usable when browser storage is unavailable. */ }
        });
    } catch (_) { /* Storage is progressive enhancement, never required for saving. */ }
})();
