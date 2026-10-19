const {chromium}=require('playwright');
const path=require('path'),assert=require('assert/strict');
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROME_BIN||'/usr/bin/google-chrome',headless:true,args:['--no-sandbox']});
 try {
  const page=await browser.newPage();page.on('dialog',d=>d.accept());
  const messages={kept:'Kept',restored:'Restored',unavailable:'Unavailable',conflict:'Conflict',restore:'Restore',discard:'Discard'};
  await page.route('http://draft.test/**',r=>r.fulfill({contentType:'text/html',body:'<form><textarea name="notes">Server original</textarea></form>'}));
  await page.goto('http://draft.test/');
  const script=path.resolve(__dirname,'../../app/core_modules/htmlelements/resources/formdrafts.js');
  async function mount(key='user:scope:form') {await page.addScriptTag({path:script});await page.evaluate(({key,messages})=>{window.draft=ChisimbaFormDrafts.attach(document.querySelector('form'),{key,fields:['notes'],messages});},{key,messages});}
  await mount();await page.locator('textarea').fill('Unsaved words');
  assert.equal(await page.evaluate(()=>draft.dirty()),true);
  await page.reload();await mount();assert.equal(await page.locator('textarea').inputValue(),'Unsaved words');
  assert.equal(await page.getByRole('status').textContent(),'Restored');
  // A changed server version is not silently overwritten.
  await page.reload();await page.locator('textarea').fill('Changed on server');await mount();
  assert.equal(await page.locator('textarea').inputValue(),'Changed on server');
  await page.getByRole('button',{name:'Restore',exact:true}).click();
  assert.equal(await page.locator('textarea').inputValue(),'Unsaved words');
  const snapshot=await page.evaluate(()=>draft.snapshot());
  await page.locator('textarea').fill('Newer words during save');
  await page.evaluate(s=>draft.saved(s),snapshot);
  assert.equal(await page.evaluate(()=>draft.dirty()),true,'In-flight edits remain dirty');
  await page.reload();await mount();await page.getByRole('button',{name:'Restore',exact:true}).click();
  assert.equal(await page.locator('textarea').inputValue(),'Newer words during save');
  // A different scope cannot consume the first scope's recovery record.
  await page.reload();await mount('user:other-scope:form');
  assert.equal(await page.locator('textarea').inputValue(),'Server original');
  await page.reload();await page.evaluate(()=>Object.defineProperty(window,'sessionStorage',{get(){throw new Error('blocked');}}));await mount();
  await page.locator('textarea').fill('Still editable');
  assert.equal(await page.getByRole('status').textContent(),'Unavailable');
  assert.equal(await page.evaluate(()=>draft.dirty()),true);
  console.log('PASS: reload, conflict review, newer edits during save, scope isolation, unavailable storage');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
