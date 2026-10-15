# Unused LiveQuery copies — 15 October 2026

Removed the jquerycore LiveQuery 1.1.1 catalogue entry/runtime and the unused
jquery/resources/jquery.livequery.js copy. The skin regression covers stale and
mixed session selections, unchanged sessions, retained Form/Validate assets,
configured core/UI/theme, setter APIs and suppression. It passed on host PHP and
local PHP 8.5. The normal local jquerycore catalogue update passed.

Also removed the unreferenced modules/resources/newsblocks.js block-editor script
and a tracked Context controller backup from August which referenced the deleted
root copy. No active controller, database, content or registration was changed.

A full PHP/JS/XML/HTML/template/CSS source trace found no current caller for the
removed root copy or orphaned script. Read-only dbTable scans across all six sites
found no stored livequery or newsblocks.js references in the inspected columns
(82/129/81/188/181/181). This does not cover encoded references, external clients
or filesystem sessions; stale session selection behaviour is covered by tests.

LiveQuery is NOT fully retired: jquery/resources/plugins/livequery/1.0.2 remains
loaded by jquery::loadLiveQueryPlugin for Grid and Boxy wrappers, Forms and
ShortURL. CMS Admin still has a LiveQuery click binding. A ShortURL occurrence is
commented code. These legacy caller modules are unregistered on all six sites;
shared wrapper invocation still matters independently of registration.

Local homepage and Personal Space browser smoke tests passed with no console
warnings/errors and no LiveQuery script loaded. No data was edited and no dormant
module enabled. This removal adds no new user journey or Help requirements.

Next: replace the Grid/Boxy dynamic event binding and CMS Admin caller before
removing the remaining runtime/loader; review associated legacy write endpoints
before reviving those modules. The remaining jquerycore catalogue entries are
Form and Validate. Global jQuery/UI and other wrapper families remain separate.
