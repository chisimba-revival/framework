# Native dynamic wrapper events

Run `php app/core_modules/jquery/tests/dynamic_events_test.php` from framework.
Warnings fail the test. This exercises the actual PHP wrappers with service
stubs, script-safe values, inline handler output and invalid native event names.

`--fixture` emits a disposable browser page using the actual generated handlers.
Boxy and grid actions are harmless recording stubs: they do not open production
dialogs, call endpoints or delete records. Check initial/nested button clicks,
keyboard activation, inserted/replaced buttons (one callback), refresh and row
arguments, and non-bubbling focus. No jQuery or LiveQuery library is loaded.

The wrappers still depend on legacy Boxy/jqGrid for their actual widgets. Native
DOM events replace LiveQuery binding; jQuery-only synthetic events/namespaces are
not supported by this native helper. Traced callers use ordinary click events.
Full dormant-module workflows, permissions and legacy multiple-row deletion
behaviour are outside this event-binding test and require review before revival.
