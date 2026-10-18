# Native timeout messages — 17 October 2026

HTML Elements 0.624 replaces timeoutMessage's jQuery close handler and duplicate
fade timer with native per-rendered-message handling. Discussion, Assignment,
quizzes, catalogue and course administration reuse this helper. No callers were
paused or given new permissions.

The dismiss button is keyboard accessible and translated. Zero/negative timeouts
remain until dismissal; positive timeouts defer while focus is within the notice.
Both display:none and visibility:hidden modes remain supported. Custom IDs and
classes are HTML-escaped; message HTML remains caller-owned, matching the existing
rich-content contract. This change does not claim to sanitise every caller.
Legacy showJScript/_jscript entry points remain, with encoded IDs and numeric
timeouts rather than interpolated JavaScript and string-evaluated timers.

Validation: PHP rendering regression with warnings promoted to exceptions;
Node execution of rendered scripts (persistence, hide mode, focus deferral and
manual dismissal); Chrome isolated fixture with no jQuery (duplicate/custom IDs,
automatic hiding, independent dismissal and keyboard activation). Local installed
PHP helper and normal HTML Elements catalogue update passed, including its new
language label. No database write workflows were exercised or changed.

Run:

    php app/core_modules/htmlelements/tests/timeoutmessage_test.php
    php app/core_modules/htmlelements/tests/timeoutmessage_test.php --render | node app/core_modules/htmlelements/tests/timeoutmessage_client_test.cjs

Browser evidence is in workspace work/native-timeout-20261017. This is a bounded
helper test, not a full application/no-jQuery release certification. Facebox alerts,
File Manager selectors and other shared callers remain. Global jQuery loading and
production remain unchanged.

Follow-up: [shared cleanup checkpoint](shared-native-cleanup-20261017.md) records
Facebox retirement, subsequent caller migrations and the remaining jQuery scope.
