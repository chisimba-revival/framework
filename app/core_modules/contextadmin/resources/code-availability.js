/* Advisory availability only: the creation service validates again on submission. */
(function () {
    'use strict';
    function start() {
        var input=document.getElementById('input_contextcode'), message=document.getElementById('contextcodemessage'), button=document.getElementById('savebutton');
        if (!input || !message || !button) return;
        var generation=0, timer;
        input.setAttribute('aria-describedby', 'contextcodemessage');
        function show(text, state, blocked) {
            message.textContent=text;
            message.classList.toggle('error',state==='error');
            message.classList.toggle('success',state==='success');
            input.classList.toggle('inputerror',state==='error');
            input.setAttribute('aria-invalid',state==='error'?'true':'false');
            button.disabled=blocked;
        }
        function check() {
            clearTimeout(timer);
            var version=++generation, code=input.value.trim().toLowerCase();
            if (!code) { show('', '', true); return; }
            if (code==='root') { show(message.dataset.reserved,'error',true); return; }
            if (!/^[a-z0-9][a-z0-9_-]{0,254}$/.test(code)) { show(message.dataset.invalid,'error',true); return; }
            show(message.dataset.checking,'',true);
            timer=setTimeout(async function () {
                try {
                    var query=new URLSearchParams({module:'contextadmin',action:'checkcode',code:code});
                    var response=await fetch('index.php?'+query.toString(),{credentials:'same-origin',headers:{Accept:'text/plain'}});
                    if (!response.ok) throw new Error('request failed');
                    var result=(await response.text()).trim();
                    if (version!==generation) return;
                    if (result==='ok') show(message.dataset.available,'success',false);
                    else if (result==='exists') show(message.dataset.exists,'error',true);
                    else if (result==='reserved' || result==='invalid') show(message.dataset[result],'error',true);
                    else throw new Error('unexpected response');
                } catch (error) {
                    // A failed advisory check is never evidence that a code is available.
                    if (version===generation) show(message.dataset.failed,'',false);
                }
            },250);
        }
        input.addEventListener('input',check);
        input.addEventListener('blur',check);
        check();
    }
    if (document.readyState==='loading') document.addEventListener('DOMContentLoaded',start); else start();
}());
