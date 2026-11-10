/* Native scoped block editing. Persist first; never replay an uncertain write. */
(function () {
    'use strict';
    var settings, busy=false, editing=false, uncertain=false;
    var sides=['left','middle','right'], selected={}, generations={};
    function byId(id){return document.getElementById(id);}
    function controls(){return document.querySelectorAll('.blockoptions button, [data-block-editor] select, #leftbutton, #middlebutton, #rightbutton');}
    function message(value){byId('block-editor-status').textContent=value;}
    function options(block){
        if(block.querySelector(':scope > .blockoptions'))return;
        var group=document.createElement('div');group.className='blockoptions';group.setAttribute('role','group');group.setAttribute('aria-label',window.blockControlsLabel);
        [['moveup',window.moveUpLabel,window.upIcon],['movedown',window.moveDownLabel,window.downIcon],['deleteblock',window.removeBlockLabel,window.deleteIcon]].forEach(function(item){
            var button=document.createElement('button');button.type='button';button.className='block-control '+item[0];button.setAttribute('aria-label',item[1]);button.title=item[1];button.innerHTML=item[2];group.append(button);
        });block.prepend(group);
    }
    function update(){
        sides.forEach(function(side){
            var container=byId(side+'blocks');if(!container)return;
            var blocks=Array.from(container.children).filter(function(e){return e.classList.contains('block');});
            blocks.forEach(function(block,i){options(block);block.classList.toggle('highlightblock',editing);block.querySelector(':scope > .blockoptions').style.display=editing?'':'none';block.querySelector('.moveup').hidden=i===0;block.querySelector('.movedown').hidden=i===blocks.length-1;});
            var add=byId(side+'addblock');if(add)add.style.display=editing?'':'none';
        });
        controls().forEach(function(e){e.disabled=busy||uncertain;});
        sides.forEach(function(side){var select=byId('dd'+side+'blocks');if(select)select.disabled=busy||uncertain;});
    }
    async function request(action,data,write){
        var body=new URLSearchParams(Object.assign({module:['context','personalspace','slate','postlogin','mylearning','myteaching','myadmin'].includes(settings.module)?settings.module:'context',pageId:settings.pageid||'',action:action,contextcode:settings.contextcode,csrf_token:settings.csrf,revision:settings.revision},data));
        try {
            var response=await fetch('index.php',{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json'},body:body});
            var result=await response.json();
            if(result.csrf_token)settings.csrf=result.csrf_token;
            if(!result.ok){if(result.code==='block_stale'||result.code==='block_uncertain')uncertain=true;var rejection=new Error(result.message||settings.denied);rejection.definitive=true;throw rejection;}
            if(result.revision)settings.revision=result.revision;
            return result;
        }catch(error){
            if(write && !error.definitive){uncertain=true;throw new Error(settings.uncertain);}
            if(!error.definitive)throw new Error(settings.failed);
            throw error;
        }
    }
    function confirmRemoval(trigger){
        return new Promise(function(resolve){
            var dialog=document.createElement('dialog');dialog.className='chisimba-form-card';
            var text=document.createElement('p');text.id='block-remove-prompt';text.textContent=window.deleteConfirm;dialog.setAttribute('aria-labelledby',text.id);dialog.append(text);
            var actions=document.createElement('div');actions.className='chisimba-form-actions';
            var cancel=document.createElement('button');cancel.type='button';cancel.textContent=settings.cancel;
            var remove=document.createElement('button');remove.type='button';remove.textContent=window.removeBlockLabel;
            cancel.addEventListener('click',function(){dialog.close();});
            remove.addEventListener('click',function(){dialog.close('remove');});
            actions.append(cancel,remove);dialog.append(actions);document.body.append(dialog);
            dialog.addEventListener('close',function(){var confirmed=dialog.returnValue==='remove';dialog.remove();trigger.focus();resolve(confirmed);},{once:true});
            dialog.showModal();cancel.focus();
        });
    }
    async function mutate(action,data,apply){
        if(busy||uncertain)return;busy=true;update();message(settings.saving);
        try {await request(action,data,true);apply();message(settings.saved);}
        catch(error){message(error.message);}
        finally{busy=false;update();}
    }
    function start(){
        var root=byId('block-editor-settings');if(!root)return;settings=root.dataset;
        sides.forEach(function(side){
            var select=byId('dd'+side+'blocks'),button=byId(side+'button'),preview=byId(side+'previewcontent');
            if(!select||!button||!preview)return;
            button.hidden=true;
            select.addEventListener('change',async function(){
                var id=select.value,version=(generations[side]||0)+1;generations[side]=version;selected[side]=null;button.hidden=true;preview.replaceChildren();
                if(!id)return;
                try{var data=await request('renderblock',{blockid:id,side:side},false);if(generations[side]!==version)return;preview.innerHTML=data.html;preview.style.display='';selected[side]=id;button.hidden=false;}
                catch(error){if(generations[side]===version)message(error.message);}
            });
            button.addEventListener('click',function(event){event.preventDefault();if(!selected[side])return;mutate('addblock',{blockid:selected[side],side:side},function(){window.location.reload();});});
            var container=byId(side+'blocks');if(!container)return;
            container.addEventListener('click',async function(event){
                var button=event.target.closest('.block-control');if(!button)return;event.preventDefault();
                var block=button.closest('.block');if(!block||block.parentElement!==container)return;
                if(button.classList.contains('deleteblock')){if(!busy&&!uncertain && await confirmRemoval(button))mutate('removeblock',{blockid:block.id},function(){block.remove();byId('editmodeswitchbutton').focus();});}
                else {var up=button.classList.contains('moveup');mutate('moveblock',{blockid:block.id,direction:up?'up':'down'},function(){var neighbour=up?block.previousElementSibling:block.nextElementSibling;if(neighbour){container.insertBefore(up?block:neighbour,up?neighbour:block);}button.focus();});}
            });
        });update();
    }
    window.switchEditMode=function(){
        if(!settings)return;editing=!editing;byId('editmodeswitchbutton').textContent=editing?window.turnEditingOff:window.turnEditingOn;
        byId('editmodeswitchbutton').setAttribute('aria-pressed', String(editing));
        var wrapper=byId('modeswitch_wrapper');
        if(wrapper){wrapper.classList.toggle('editing_on',editing);wrapper.classList.toggle('editing_off',!editing);}
        update();
    };
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
}());
