# Native tagging and retirement of remote block imports

## Behaviour

Tagging 1.003 replaces the old synchronous jQuery tagSuggest plugin with a
progressively enhanced text input and native datalist. Commas separate tags;
suggestions complete the last tag while retaining the preceding text. Requests
are debounced, abortable and protected against stale replies. Failure leaves
the draft editable; subsequent input retries. New tags can always be typed.

Suggestions and the tagging landing page now show only the current signed-in
account's ordinary tags. The landing page previously exposed site-wide metadata
without checking the visibility of the underlying items. It now lists owner-scoped
counts, without pretending that a tag grants access to an item. Prepared queries
escape LIKE metacharacters and bound result sizes. The old Blog import GET action
returns 410; paused Blog data is untouched. This is not a revival of its legacy
write APIs. New scoped classification work should use classification.

Dynamiccanvas 0.114 retires the anonymous arbitrary-URL proxy (HTTP 410), the
server-side remote HTML importer, and the unused jQuery renderEmbed helper.
Stored externalblock definitions produce a translated unavailable message and
make no network request. Local block rendering remains supported. Invalid-block
diagnostics escape stored markup; the controller permits only its intended demo
and retired-proxy actions rather than arbitrary internal helper dispatch.

The separate blocks provider's old showBlockExternal API/configuration has no
source callers but is not the remote-fetch mechanism changed here.

## Evidence and validation

On 17 October 2026, read-only scans on local Chisimba and all six production
sites found no externalblock, renderEmbed, dynamiccanvas or ajaxtags references
in the audited text/configuration columns, except the old external-block language
key and configuration description. Tag records still exist and are preserved.
Source tracing found ajaxtags only in its demonstration and no callers of
renderEmbed. The externalblocks receiving module is absent.

Repeatable source tests:

- `php app/core_modules/tagging/tests/private_suggestions_test.php`
- `node app/core_modules/tagging/tests/native_tags_test.js`
- `php app/core_modules/dynamiccanvas/tests/remote_blocks_test.php`

All passed, alongside PHP lint and diff whitespace checks. Disposable PDO/HTTP
checks verified exact percent-prefix matching, apostrophes/injection attempts,
missing owner and oversized input, cross-user isolation including spoofed owner
parameters, anonymous denial, escaped tag text, retired Blog import, and 410 for
remote proxy targets including HTTP loopback, file and metadata URLs. Local-block
rendering and escaped invalid-block diagnostics passed the focused source test.

Chrome on the isolated no-jQuery site verified the labelled input, correct
comma-prefixed datalist options, contextual Help/full guide, Escape/focus return,
no loaded jQuery assets and no console warnings/errors. Browser automation did
not expose the browser-owned datalist popup sufficiently to verify selection
with arrow keys/Enter; the generated options were verified through the DOM.
No narrow-viewport or screen-reader session was performed. Native datalist
accessibility varies by browser; manual text entry remains available.
The scoped fixture logs contained no PHP warnings/deprecations; fresh catalogue
installation logged its informational absence of dynamiccanvas defaultdata.

Local catalogue updates do not deploy this batch to production. Dynamiccanvas
is not enabled on local if dormant. Test containers, clone and relay are removed
after validation; fixture scripts/evidence are kept outside the repositories.

## Global jQuery status

Do not disable the global loader yet. Source-confirmed remaining loads include
File Manager's previewfolder -> thumbnails.js and Workgroup's main template ->
workgroup.js (file-list Ajax). Legacy login support and Context utility methods
also need reachability tracing, as do the deferred CMS consumers. This batch
removes two dependencies; it does not certify every active page as jQuery-free.
