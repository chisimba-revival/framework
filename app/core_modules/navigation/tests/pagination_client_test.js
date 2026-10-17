const fs = require('node:fs'), vm = require('node:vm'), assert = require('node:assert/strict');
class Element {
    constructor() { this.dataset = {}; this.attributes = {}; this.events = {}; this.children = []; }
    setAttribute(k, v) { this.attributes[k] = v; }
    addEventListener(k, f) { this.events[k] = f; }
    append(e) { this.children.push(e); }
    replaceChildren() { this.children = []; }
    querySelector() { return this.children.find(e => e.attributes['aria-current']); }
    focus() { this.focused = true; }
}
const root = new Element(), results = new Element(), links = new Element(), status = new Element();
results.id = 'results';
root.dataset = {pages: '30', page: '0', url: '/index.php?module=fixture', loading: 'Loading', error: 'Retry'};
root.querySelector = selector => ({'[data-pagination-results]': results, '[data-pagination-links]': links, '[data-pagination-status]': status})[selector];
const queue = [];
vm.runInNewContext(fs.readFileSync(__dirname + '/../resources/pagination.js', 'utf8'), {
    document: {readyState: 'complete', querySelectorAll: () => [root], createElement: () => new Element()},
    location: {href: 'https://fixture.invalid/index.php', origin: 'https://fixture.invalid'}, URL, AbortController,
    fetch: (url, options) => new Promise((resolve, reject) => queue.push({url, options, resolve, reject}))
});
const flush = () => new Promise(resolve => setImmediate(resolve));
const click = page => links.events.click({button: 0, target: {closest: () => ({dataset: {page}})}, preventDefault() {}});
(async () => {
    queue.shift().resolve({ok: true, text: async () => 'first'}); await flush();
    assert.equal(results.innerHTML, 'first'); assert.equal(links.children.length, 6);
    click('1'); const stale = queue.shift(); click('2'); const current = queue.shift();
    assert.equal(stale.options.signal.aborted, true);
    current.resolve({ok: true, text: async () => 'current'}); await flush();
    stale.resolve({ok: true, text: async () => 'stale'}); await flush();
    assert.equal(results.innerHTML, 'current'); assert.equal(root.dataset.page, '2');
    assert.equal(links.querySelector().focused, true);
    click('3'); queue.shift().reject(new Error('offline')); await flush();
    assert.equal(results.innerHTML, 'current'); assert.equal(status.textContent, 'Retry'); assert.equal(results.attributes['aria-busy'], 'false');
    click('2'); queue.shift().resolve({ok: true, redirected: true, text: async () => 'login'}); await flush();
    assert.equal(results.innerHTML, 'current');
    click('3'); queue.shift().resolve({ok: true, text: async () => 'recovered'}); await flush();
    assert.equal(results.innerHTML, 'recovered'); assert.equal(status.textContent, '');
    console.log('PASS pagination loading, bounded links, stale-response protection, focus, failure retention and retry');
})().catch(error => { console.error(error); process.exitCode = 1; });
