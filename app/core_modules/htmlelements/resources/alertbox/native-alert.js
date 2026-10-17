/* Local-fragment previews. Remote pages deliberately retain normal navigation. */
(function () {
    'use strict';
    if (window.chisimbaNativeAlerts) return;
    window.chisimbaNativeAlerts = true;
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('a[data-native-alert]');
        if (!trigger || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const href = trigger.getAttribute('href');
        if (!href || href[0] !== '#' || !window.HTMLDialogElement) return;
        let id;
        try { id = decodeURIComponent(href.slice(1)); } catch (_) { return; }
        const source = document.getElementById(id);
        if (!source || source.contains(trigger) || source.closest('dialog, form')) return;
        const dialog = document.createElement('dialog');
        if (typeof dialog.showModal !== 'function') return;
        event.preventDefault();
        dialog.className = 'chisimba-content-dialog';
        dialog.setAttribute('aria-label', trigger.textContent.trim());
        const close = document.createElement('button');
        close.type = 'button';
        close.textContent = trigger.dataset.closeLabel;
        const content = document.createElement('div');
        const marker = document.createComment('native alert content');
        const hidden = source.hidden;
        const display = source.style.display;
        source.before(marker);
        source.hidden = false;
        source.style.display = 'block';
        content.append(source);
        dialog.append(close, content);
        document.body.append(dialog);
        dialog.addEventListener('close', function () {
            marker.replaceWith(source);
            source.hidden = hidden;
            source.style.display = display;
            dialog.remove();
            if (trigger.isConnected) trigger.focus();
        }, {once: true});
        close.addEventListener('click', function () { dialog.close(); });
        dialog.showModal();
        close.focus();
    });
}());
