# Native feature boxes and retirement of the old canvas editor

## Result

Navigation 1.050 renders toggled feature boxes as native `details`/`summary`
disclosures. Initial `none` becomes collapsed; other existing initial states are
expanded. Titles/headings, content and IDs remain available. Untitled, untoggled
and content-presentation variants keep their existing rendering. The shared skin
owns header, marker, focus and compact layout; no JavaScript loader is required.

Canvas 0.051 removes `resources/canvasblocks.js` and its loader. Slate is the only
active `buildcanvas::show()` caller and already supplies the native context editor.
Personal/module renderers are used only by paused Myprofile/About and the archived
Bannerhelper review copy. They preserve rendered columns but no longer offer an
unprotected legacy editor. Page rendering without owner-supplied editor settings
is likewise read-only. A future revival must supply a scoped repository and
protected native editor; do not restore the old GET mutation client.

Slate 0.018 now supplies editing settings/tokens/Help only for current persisted
administrators. Public pages retain read-only content. Its existing protected save
routes remain in place. **Release framework and modules together.** Neither Slate
nor any paused module was enabled on the working local site or production.

## Evidence

* Source tracing, including ignored/paused source, found no other renderer caller.
  Several active dashboard modules call only the shared switch-label helper.
* Read-only selected text/HTML/configuration column scans on local and all six
  existing sites found no stored references to `canvasblocks.js`, `buildcanvas`
  or `toggleIt`. This does not claim an exhaustive scan of every binary or column.
* Real featurebox renderer tests cover default-open, collapsed, untitled,
  untoggled, content mode, escaped attributes and absence of a script dependency.
* Real canvas routing tests cover preserved read-only personal/module/page content
  and explicit native editor configuration. The obsolete jQuery-mock preview test
  was removed; race, duplicate, token and uncertain-write behaviour are exercised
  by `context/tests/native_block_client_test.js`, including Slate mode.
* Existing navigation, language chooser, canvas chooser, Postlogin policy and
  Slate migration tests pass. Slate settings tests cover anonymous, cached-but-
  revoked administrator and current administrator cases.
* PHP lint and diff whitespace checks pass.
* Chrome on an isolated PHP 8.5/PDO clone with automatic jQuery/UI suppressed:
  anonymous homepage disclosures toggle with click and Enter, retaining controls
  when reopened; no console errors; shared styling visually checked.
* A fresh disposable Slate installation: public view exposes no editor; admin
  editing switch, preview, persisted add/reload, cancellation by Escape, confirmed
  removal and quick/full Help with Escape all pass without jQuery. The test block
  was removed. No new PHP warning/deprecation/fatal log matches were found.

Disposable containers, database, relay and credentials are removed after testing.
There is no production deployment and normal core jQuery loading remains enabled.

The refreshed inventory is still a search-candidate inventory, not a count of live
uses: 372 files with jQuery API candidates, including vendors and historical code.
Further removal requires tracing active callers and embedded blocks/editors before
changing the global loader.
