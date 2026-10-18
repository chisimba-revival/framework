# Final LiveQuery runtime retirement — 15 October 2026

Replaced the Grid/Boxy wrappers' remaining LiveQuery bindings with a shared native
DOM event-script helper in the jquery wrapper. It uses ID lookup at event time,
so inserted and replaced controls work without rescanning the DOM or rebinding.
Capture supports non-bubbling events on the exact target; bubbling events from
children match their bound ancestor. All traced callers use click events.
Native event names are validated and IDs/dialog strings/row IDs are JSON-encoded
with script-safe flags. jQuery-only synthetic events/namespaces are not supported.
The actual Boxy and jqGrid widgets remain legacy dependencies.

Fixed Boxy's inline binding helper, which previously printed dialog HTML instead
of its generated script. The normal helper now respects its event argument.
Removed redundant Forms/ShortURL loads, a commented ShortURL callback, and the
CMS Admin debug alert on its ordinary Add Content link. The link keeps its
normal destination. Removed the final LiveQuery runtime and loader method.

The earlier six-site scan found Forms, ShortURL and CMS Admin unregistered and
no stored LiveQuery references in inspected text/configuration columns. No module
was enabled. The removal does not certify dormant modules' write endpoints or
permissions. In particular existing grid multiple-row deletion behaviour is not
changed or exercised against data; review it before revival.

Host and local PHP 8.5 wrapper tests passed. Strict PHP 8.5 lint passed for changed
callers. Browser tests using actual generated scripts and harmless widget stubs
passed: nested button clicks, keyboard activation, inline binding, inserted and
replaced buttons with one callback, quoted/script-like titles and row IDs,
refresh targeting and non-bubbling focus. No console warnings/errors occurred.
The test fixture loads neither jQuery nor LiveQuery and invokes no write endpoints.
No new user journey was introduced; existing module Help remains unchanged.

[Six-site deployment and verification](../native-livequery-rollout-20261015.md).
