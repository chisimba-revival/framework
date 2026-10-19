/* Shared settings enhancement. Server admission validation remains authoritative. */
(function () {
    'use strict';
    function start() {
        var box = document.getElementById('context_private_admission');
        var policies = document.querySelectorAll('input[name="access_policy"]');
        function admission() {
            if (!box) return;
            var selected = document.querySelector('input[name="access_policy"]:checked');
            var paid = selected && selected.value === 'private';
            var row = box.closest('tr');
            if (row) row.hidden = !paid;
            box.querySelectorAll('input').forEach(function (input) { input.disabled = !paid; });
        }
        policies.forEach(function (input) { input.addEventListener('change', admission); });
        admission();
        var select = document.getElementById('input_delivery_format');
        var help = document.getElementById('context-delivery-format-help') || document.getElementById('delivery-format-help');
        if (!select || !help) return;
        var descriptions = {};
        try { descriptions = JSON.parse(help.dataset.formatDescriptions || '{}') || {}; } catch (error) { /* Optional guidance must not break editing. */ }
        function formatHelp() { help.textContent = typeof descriptions[select.value] === 'string' ? descriptions[select.value] : ''; }
        select.addEventListener('change', formatHelp);
        formatHelp();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}());
