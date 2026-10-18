const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../resources/settings.js','utf8');
function fixture(data,ready='complete',missing=false){
 const row={}, inputs=[{checked:true,disabled:false},{checked:false,disabled:false}], events={};
 const policies=[{value:'private',addEventListener(k,f){events.policy=f;}}];
 let selected=policies[0];
 const select={value:'standard',addEventListener(k,f){events.format=f;}},help={dataset:{formatDescriptions:data},textContent:''};
 const box={closest:()=>row,querySelectorAll:()=>inputs};
 const doc={readyState:ready,addEventListener(k,f){events.ready=f;},getElementById:id=>missing?null:({'context_private_admission':box,'input_delivery_format':select,'context-delivery-format-help':help}[id]||null),querySelectorAll:()=>policies,querySelector:()=>selected};
 vm.runInNewContext(source,{document:doc});if(events.ready)events.ready();
 return {row,inputs,events,select,help,setPolicy(v){selected={value:v};events.policy();}};
}
for(const ready of ['loading','complete']){
 const f=fixture(JSON.stringify({standard:'Standard',microlearning:'<strong>Short</strong>',masterclass:'Focused'}),ready);
 assert.equal(f.row.hidden,false);assert.equal(f.help.textContent,'Standard');
 for(const policy of ['public','free','tier_1','tier_2']){f.setPolicy(policy);assert.equal(f.row.hidden,true);assert(f.inputs.every(i=>i.disabled));}
 f.setPolicy('private');assert.equal(f.row.hidden,false);assert(f.inputs.every(i=>!i.disabled));assert(f.inputs[0].checked,'admission selection retained');
 f.select.value='microlearning';f.events.format();assert.equal(f.help.textContent,'<strong>Short</strong>','guidance remains text');
 f.select.value='unknown';f.events.format();assert.equal(f.help.textContent,'');
}
for(const value of ['invalid','null','[]'])assert.equal(fixture(value).help.textContent,'');
fixture('{}','complete',true);
console.log('PASS native settings initial state, all policies, retained selection, text-only guidance, readiness and missing/malformed optional data');
