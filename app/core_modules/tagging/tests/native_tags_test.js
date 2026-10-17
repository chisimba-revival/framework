const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const pending = [];
let listener, timer;
const list = {options: [], replaceChildren() { this.options = []; }, appendChild(o) { this.options.push(o.value); }};
const input = {value: '', dataset: {tagSuggestions: '/index.php?module=tagging&action=gettags'}, getAttribute() { return 'list'; }, addEventListener(name, fn) { listener = fn; }};
const context = {URL, AbortController, location: {href: 'https://example.invalid/index.php', origin: 'https://example.invalid'},
 document: {readyState: 'complete', querySelectorAll() { return [input]; }, getElementById() { return list; }, createElement() { return {}; }},
 setTimeout(fn) { timer = fn; }, clearTimeout() { timer = null; },
 fetch(url, options) { return new Promise((resolve, reject) => pending.push({url, options, resolve, reject})); }};
vm.runInNewContext(fs.readFileSync(__dirname + '/../resources/native-tags.js', 'utf8'), context);
function edit(value) { input.value = value; listener(); const fn = timer; timer = null; return fn && fn(); }
function result(index, names) { pending[index].resolve({ok:true, json: async()=>names}); }
(async () => {
 const first = edit('Birds, bi'); result(0, ['Birds', 'Birdwatching', '<img src=x>', 'comma,tag']); await first;
 assert.deepEqual(list.options, ['Birds, Birdwatching', 'Birds, <img src=x>']);
 const older = edit('old'); const newer = edit('new');
 assert.equal(pending[1].options.signal.aborted, true);
 result(2, ['new result']); await newer; result(1, ['old result']); await older;
 assert.deepEqual(list.options, ['new result']);
 const failed = edit('my draft'); pending[3].reject(new Error('offline')); await failed;
 assert.equal(input.value, 'my draft'); assert.deepEqual(list.options, []);
 const retry = edit('my draf'); result(4, ['my draft']); await retry; assert.deepEqual(list.options, ['my draft']);
 edit(''); assert.deepEqual(list.options, []);
 assert.equal(pending[0].url.searchParams.get('tag'), 'bi');
 console.log('PASS comma completion, duplicate filtering, safe values, stale/aborted requests, failure recovery');
})();
