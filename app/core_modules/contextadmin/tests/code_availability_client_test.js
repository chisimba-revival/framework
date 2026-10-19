const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const requests=[],timers=new Map();let next=0;
function element(){return {events:{},dataset:{},classList:{toggle(){}},setAttribute(){},addEventListener(k,f){this.events[k]=f;}};}
const input=element(),message=element(),button=element();input.value='';
message.dataset={reserved:'Reserved',invalid:'Invalid',checking:'Checking',available:'Available',exists:'Exists',failed:'Try again'};
const document={readyState:'complete',getElementById:id=>({input_contextcode:input,contextcodemessage:message,savebutton:button}[id])};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/code-availability.js','utf8'),{document,URLSearchParams,setTimeout:f=>{timers.set(++next,f);return next;},clearTimeout:id=>timers.delete(id),fetch:url=>new Promise((resolve,reject)=>requests.push({url,resolve,reject}))});
function change(value){input.value=value;input.events.input();}
function run(){const callbacks=[...timers.values()];timers.clear();callbacks.forEach(f=>f());}
const tick=()=>new Promise(r=>setImmediate(r));
(async()=>{
 assert.equal(button.disabled,true);
 change('ROOT');assert.equal(message.textContent,'Reserved');assert.equal(button.disabled,true);
 change('a&b');assert.equal(message.textContent,'Invalid');assert.equal(requests.length,0);
 change('old');run();change(' New_code ');run();
 assert(requests[1].url.endsWith('code=new_code'));
 requests[1].resolve({ok:true,text:async()=>'ok'});await tick();assert.equal(message.textContent,'Available');
 requests[0].resolve({ok:true,text:async()=>'exists'});await tick();assert.equal(message.textContent,'Available','stale response ignored');
 change('duplicate');run();requests[2].resolve({ok:true,text:async()=>'exists'});await tick();assert.equal(button.disabled,true);
 change('other');run();requests[3].resolve({ok:true,text:async()=>'<html>Login</html>'});await tick();assert.equal(message.textContent,'Try again');assert.equal(button.disabled,false,'server still validates submission');
 input.events.blur();run();requests[4].reject(new Error('offline'));await tick();assert.equal(message.textContent,'Try again');
 change('late');run();change('');requests[5].resolve({ok:true,text:async()=>'ok'});await tick();assert.equal(message.textContent,'');assert.equal(button.disabled,true,'clearing invalidates in-flight request');
 change('one');change('two');assert.equal(timers.size,1,'debounced');
 console.log('PASS native availability: reserved/invalid/blank, normalisation, stale responses, unknown replies, network failure, retry and debounce');
})();
