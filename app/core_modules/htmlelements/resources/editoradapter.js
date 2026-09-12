/* The native rich-text lifecycle is shared by full pages and partial updates. */
(function () {
    'use strict';
    if (window.ChisimbaEditorMount) return;
    window.ChisimbaEditorMount = function (root) {
        return Promise.all(Array.from(root.querySelectorAll('[data-chisimba-editor]')).map(function (field) {
            if (!window.tinymce || tinymce.get(field.id) || field.dataset.editorMounting) return;
            var config = JSON.parse(field.dataset.chisimbaEditor);
            delete config.selector;
            config.target = field;
            config.plugins = (config.plugins + ' fullscreen').trim();
            config.toolbar += ' | fullscreen';
            config.file_picker_callback = function (callback) {
                window.ChisimbaEditor.beginFilePick(callback);
                window.open(field.dataset.editorPicker, 'chisimba_image_picker', 'width=1000,height=720,resizable=yes,scrollbars=yes');
            };
            field.dataset.editorMounting = '1';
            return tinymce.init(config).catch(function (error) {
                // Leave the original textarea usable if rich-text enhancement fails.
                field.style.display = '';
                console.error('Chisimba editor initialization failed.', error);
            }).finally(function () { delete field.dataset.editorMounting; });
        }));
    };
    window.ChisimbaEditorUnmount = function (root) {
        root.querySelectorAll('[data-chisimba-editor]').forEach(function (field) {
            var editor = window.tinymce && tinymce.get(field.id);
            if (editor) editor.remove();
        });
    };
    function start() { window.ChisimbaEditorMount(document); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
}());
