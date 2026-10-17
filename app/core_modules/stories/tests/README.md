# Stories native reading — 17 October 2026

Run `php app/core_modules/stories/tests/native_stories_test.php` from the framework
root. It exercises the actual summary/archive producer with deterministic records,
escaped titles, bound category selection and public fragment access decisions.

Stories 1.253 uses native details/summary disclosures for full text and archives.
No jQuery or asynchronous request is required to expand/collapse. The existing
archive endpoints remain compatible; public ID-based fragments only return active
prelogin stories. Missing/inactive/private records return 404. Expiration remains
the legacy display indicator, not a new publication rule. Active categories remain
available to their existing trusted producers.

Administration now requires current persisted site-admin membership. Save/delete
require POST and shared CSRF. Rejected saves retain submitted fields and renew the
token; write exceptions also return the draft. Deletion uses a disclosure followed
by a POST confirmation. The existing rich-text editor is retained; the date input
uses the native browser picker. This is not a full redesign of the legacy editor.

Disposable PDO/HTTP checks performed:

- Public active, inactive, private and missing story requests.
- Native archive/full-text rendering; bound queries and output escaping.
- Administrator edit and successful save; expired-token draft preservation/retry.
- Ordinary-member and revoked-administrator denial.
- Destructive GET rejection and valid POST deletion of a synthetic record.
- Normal Module Catalogue update, changed PHP lint and whitespace checks.

Chrome checks with automatic jQuery suppressed: story and archive expansion,
keyboard collapse, full-page reading, compact/full Help and Escape/focus return,
editor load and save. No jQuery assets on the reading page and no console errors
observed in these checks. Final scoped PHP logs contained no warnings/deprecations.
Narrow viewport and a comprehensive redesign of authoring were not part of this
batch. No production deployment. Global jQuery is still present for other callers.
