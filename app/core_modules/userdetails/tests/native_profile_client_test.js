/* Native client contracts using disposable DOM/fetch adapters; no browser session or network. */
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
class Element {
 constructor(){this.events={};this.value='';this.children=[];this.dataset={};}
 addEventListener(name,fn){this.events[name]=fn;}
 setAttribute(){} removeAttribute(){} replaceChildren(){this.children=[];this.textContent='';} append(e){this.children.push(e);} focus(){}
}
const root=new Element(),school=new Element(),id=new Element(),results=new Element(),form=new Element(),status=new Element(),token=new Element(),button=new Element();
root.dataset={schoolUrl:'/schools',saving:'Saving',failed:'Failed',uncertain:'Check first',noSchools:'No matches'};school.form=new Element();results.id='profile-school-results';form.action='/photo';form.querySelector=s=>s==='[data-profile-status]'?status:token;
let queue=[],timer;const document={readyState:'complete',querySelector:()=>root,querySelectorAll:s=>s==='form[data-profile-action]'?[form]:s.includes('button')?[button]:[],getElementById:s=>({'input_school':school,'input_school_id':id,'profile-school-results':results}[s]),createElement:()=>new Element()};
vm.runInNewContext(fs.readFileSync(__dirname+'/../resources/userdetails.js','utf8'),{document,location:{href:'https://fixture.invalid/'},URL,AbortController,FormData:class{},setTimeout:f=>(timer=f,1),clearTimeout:()=>{timer=null;},fetch:()=>new Promise((resolve,reject)=>queue.push({resolve,reject}))});
(async()=>{
 id.value='previous';school.value='New school';school.events.input();assert.equal(id.value,'');let first=timer();
 school.value='Other school';school.events.input();let second=timer();queue.shift().resolve({ok:true,json:async()=>[{label:'Stale',value:'stale'}]});await first;assert.equal(results.children.length,0);
 queue.shift().resolve({ok:true,json:async()=>[{label:'Chosen',value:'chosen'}]});await second;results.children[0].events.click();assert.equal(school.value,'Chosen');assert.equal(id.value,'chosen');
 school.value='Unknown';school.events.input();let empty=timer();queue.shift().resolve({ok:true,json:async()=>[]});await empty;assert.equal(results.textContent,'No matches');
 school.events.keydown({key:'Escape'});assert.equal(results.children.length,0);assert.equal(results.textContent,'');
 let pending=form.events.submit({preventDefault(){}});assert.equal(button.disabled,true);await form.events.submit({preventDefault(){}});assert.equal(queue.length,1,'duplicate blocked');queue.shift().reject(new Error('lost response'));await pending;assert.equal(status.textContent,'Check first');assert.equal(button.disabled,false);
 let retry=form.events.submit({preventDefault(){}});queue.shift().resolve({json:async()=>({ok:false,message:'Expired',csrf_token:'renewed'})});await retry;assert.equal(token.value,'renewed');assert.equal(status.textContent,'Expired');
 console.log('PASS stale school results, selection invalidation, empty results, Escape, duplicate requests and interrupted/expired responses');
})().catch(e=>{console.error(e);process.exitCode=1;});
