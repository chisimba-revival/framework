const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
class Element {
 constructor(){this.events={};this.children=[];this.style={};this.dataset={};this.value='';this.classList={toggle(){}};}
 addEventListener(k,v){this.events[k]=v;} replaceChildren(){this.children=[];} focus(){} setAttribute(){}
}
const elements={};['block-editor-settings','block-editor-status','ddleftblocks','leftbutton','leftpreviewcontent','leftblocks','leftaddblock','editmodeswitchbutton','modeswitch_wrapper'].forEach(id=>elements[id]=new Element());
elements['block-editor-settings'].dataset={contextcode:'fixture',csrf:'old',revision:'original',saving:'Saving',saved:'Saved',failed:'Failed',uncertain:'Reload first',denied:'Denied'};
const select=elements.ddleftblocks,button=elements.leftbutton,status=elements['block-editor-status'];
const queue=[];let reloads=0;
const document={readyState:'complete',getElementById:id=>elements[id],querySelectorAll:()=>[button],createElement:()=>new Element()};
const window={location:{reload(){++reloads;}}};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/contextblocks.js','utf8'),{document,window,URLSearchParams,fetch:()=>new Promise((resolve,reject)=>queue.push({resolve,reject}))});
const flush=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
 select.value='block|old|fixture';let first=select.events.change();select.value='block|new|fixture';let second=select.events.change();
 queue.shift().resolve({json:async()=>({ok:true,html:'stale'})});await first;assert.notEqual(elements.leftpreviewcontent.innerHTML,'stale');
 queue.shift().resolve({json:async()=>({ok:true,html:'current'})});await second;assert.equal(elements.leftpreviewcontent.innerHTML,'current');assert.equal(button.hidden,false);
 button.events.click({preventDefault(){}});button.events.click({preventDefault(){}});assert.equal(queue.length,1,'duplicate save prevented');assert.equal(select.disabled,true);
 queue.shift().resolve({json:async()=>({ok:false,code:'block_csrf',message:'Expired',csrf_token:'renewed'})});await flush();assert.equal(status.textContent,'Expired');assert.equal(button.disabled,false);assert.equal(elements['block-editor-settings'].dataset.csrf,'renewed');assert.equal(select.value,'block|new|fixture');
 button.events.click({preventDefault(){}});queue.shift().reject(new Error('lost response'));await flush();assert.equal(status.textContent,'Reload first');assert.equal(button.disabled,true);assert.equal(select.disabled,true);assert.equal(reloads,0);button.events.click({preventDefault(){}});assert.equal(queue.length,0,'uncertain write never replayed');
 console.log('PASS stale previews, duplicate prevention, CSRF renewal, preserved selection and uncertain-write lockout');
})().catch(error=>{console.error(error);process.exitCode=1;});
