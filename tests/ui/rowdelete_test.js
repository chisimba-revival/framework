/* Behaviour of the real client, without site data or a jQuery global. */
const fs = require('node:fs'), vm = require('node:vm'), assert = require('node:assert/strict');
async function run(outcome) {
    let handler, calls=0, removed=false, focused=false, release;
    const error={textContent:''}, status={textContent:''};
    const row={remove(){removed=true;}};
    const list={querySelector(){return status;},focus(){focused=true;}};
    const wrapper={querySelector(){return error;}};
    const button={disabled:false,dataset:{url:'/delete',id:'fixture',revision:'revision',csrf:'token',confirm:'Confirm?',failure:'Refresh before retrying',success:'Deleted'},setAttribute(){},removeAttribute(){},closest(s){return s==='tr'?row:s==='[data-row-delete-list]'?list:wrapper;}};
    const ctx={URL,URLSearchParams,window:{location:{href:'https://example.test/list',origin:'https://example.test'},confirm(){return outcome!=='cancel';}},document:{documentElement:{dataset:{}},addEventListener(name,fn){handler=fn;}},fetch:async(url,options)=>{
        calls++;assert.equal(url,'https://example.test/delete');assert.equal(options.method,'POST');assert.equal(options.redirect,'error');assert.equal(options.credentials,'same-origin');
        assert.equal(options.body.get('csrf_token'),'token');assert.equal(options.body.get('revision'),'revision');assert.equal(options.body.get('id'),'fixture');
        if(outcome==='network')throw Error('Network');
        if(outcome==='pending')await new Promise(resolve=>{release=resolve;});
        return {ok:outcome!=='reject',text:async()=>outcome==='html'?'<html>Sign in</html>':outcome==='reject'?'Denied':'RECORD_DELETED'};
    }};
    if(outcome==='external')button.dataset.url='https://other.test/delete';
    vm.runInNewContext(fs.readFileSync(__dirname+'/../../app/core_modules/ui/resources/js/rowdelete.js','utf8'),ctx);
    const event={target:{closest(){return button;}}};
    const task=handler(event);
    if(outcome==='pending'){assert.equal(button.disabled,true);await handler(event);assert.equal(calls,1);release();}
    await task;
    assert.equal(button.disabled,false);
    assert.equal(removed,['success','pending'].includes(outcome));assert.equal(focused,removed);
    assert.equal(calls,['cancel','external'].includes(outcome)?0:1);
    if(!['success','pending','cancel'].includes(outcome))assert.equal(error.textContent,'Refresh before retrying');
    if(removed)assert.equal(status.textContent,'Deleted');
}
(async()=>{for(const result of ['success','cancel','pending','reject','network','html','external'])await run(result);console.log('PASS native deletion: confirmation, POST/CSRF/revision, duplicate prevention, failure preservation, redirect/HTML rejection and focus');})().catch(e=>{console.error(e);process.exitCode=1;});
