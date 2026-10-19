/* Optional CPD controls on the creation form; validation remains server-owned. */
(function () {
    'use strict';
    function start() {
        var enabled=document.getElementById('cpd_enabled'), panel=document.getElementById('cpd_creation_settings');
        if (!enabled || !panel) return;
        function toggle() {
            panel.hidden=!enabled.checked;
            ['cpd_scheme_id','cpd_category_id','cpd_points','cpd_reason'].forEach(function(id){var input=document.getElementById(id);if(input)input.required=enabled.checked;});
        }
        var scheme=document.getElementById('cpd_scheme_id'), category=document.getElementById('cpd_category_id');
        function filter() {
            if (!scheme || !category) return;
            Array.from(category.options).forEach(function(option){if(option.dataset.scheme!==undefined)option.hidden=option.dataset.scheme!==scheme.value;});
            var selected=category.options[category.selectedIndex];
            if (selected && selected.dataset.scheme!==scheme.value) category.value='';
        }
        enabled.addEventListener('change',toggle);
        if(scheme)scheme.addEventListener('change',filter);
        toggle();filter();
        var format=document.getElementById('input_delivery_format'),navigation=document.getElementById('input_navigation_mode');
        if(format && navigation)format.addEventListener('change',function(){navigation.value=format.value==='microlearning'?'backward':'free';});
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
}());
