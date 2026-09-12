const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
for(const file of ['../resources/canvasblocks.js','../../context/resources/contextblocks.js']) {
 const requests=[];const chain=new Proxy({}, {get:()=>()=>chain});
 const jq=()=>chain;jq.ajax=request=>requests.push(request);
 const context={jQuery:jq,document:{},theModule:'postlogin',pageId:'',console};context.window=context;
 vm.createContext(context);vm.runInContext(fs.readFileSync(path.join(__dirname,file),'utf8'),context);
 context.addBlock(false,'middle');assert.equal(requests.length,0,'No false block submitted');
 context.getPreview('block|siteblog|simpleblog','middle');
 context.getPreview('block|latestblogs|simpleblog','middle');
 requests[0].success('<div>Old selection</div>');assert.equal(context.middleBlock,false,'Late preview must not activate old selection');
 requests[1].success('<div>Current selection</div>');assert.equal(context.middleBlock,'block|latestblogs|simpleblog');
 context.getPreview('','middle');context.addBlock(context.middleBlock,'middle');assert.equal(requests.length,2,'Clearing selection cannot submit a block');
}
console.log('PASS: invalid selection and out-of-order preview responses');
