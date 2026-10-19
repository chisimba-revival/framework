const fs=require('fs'),vm=require('vm'),assert=require('assert');
// Pipe timeoutmessage_test.php --render into this test.
const html=fs.readFileSync(0,'utf8');
const scripts=[...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(m=>m[1]);
for(let i=0;i<scripts.length;i++) {
 const callbacks=[];const events={};const el={style:{},contains:a=>a===el,querySelector:()=>({addEventListener:(n,f)=>events[n]=f})};
 const doc={currentScript:{previousElementSibling:el},activeElement:null};
 vm.runInNewContext(scripts[i],{document:doc,setTimeout:(f,d)=>{callbacks.push(f);return callbacks.length},clearTimeout:()=>{}});
 assert.equal(callbacks.length,i===1?1:0);
 if(i===1){doc.activeElement=el;callbacks[0]();assert.equal(el.style.visibility,undefined);doc.activeElement=null;callbacks[1]();assert.equal(el.style.visibility,'hidden');}
 else{events.click();assert.equal(el.style.display,'none');}
}
console.log('PASS zero/negative persistence, independent dismissal, hidden mode, focus deferral');
