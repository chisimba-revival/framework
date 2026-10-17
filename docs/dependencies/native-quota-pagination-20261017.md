# Native quota pagination — 17 October 2026

Navigation 1.052 replaces its bundled jQuery pagination plugin with semantic page
links and native fetch. The existing public component contract is retained for
File Manager and Context Statistics. Requests are same-origin; superseded requests
cannot replace newer results, failure retains the previous results with a retry
message, and keyboard activation restores focus to the current page. The old
plugin JavaScript, CSS and accompanying vendor documents are removed.

File Manager 1.112 migrates quota search switching, labels the form controls and
edit icons, and adds shared contextual Help. All quota routes now require current
persisted administrator membership. Saves require POST and an allocation-bound
shared CSRF token. Expired tokens, invalid allocations, write failures and stale
edits retain drafts. Custom allocations are whole nonnegative megabytes within
the stored integer range. An atomic comparison prevents overwriting a changed
allocation. Searches bind data through dbTable and allowlist fields/order; paging
is bounded and stable. Names are escaped at output. Relevant dynamic-property,
null-trim and strftime warnings were fixed at their cause.

Validation:

- `php app/core_modules/filemanager/tests/quota_query_test.php`
- `php app/core_modules/filemanager/tests/quota_save_test.php`
- `node app/core_modules/navigation/tests/pagination_client_test.js`
- Changed PHP syntax and git whitespace checks.
- Disposable PDO clone: hostile query inputs, page bounds, valid save, stale atomic
  update rejection; HTTP GET/invalid token/invalid value failures, preserved drafts,
  anonymous/member/revoked administrator denial, including mixed-case actions.
- Chrome with automatic jQuery suppressed: both search scopes, synthetic save,
  two-page results, keyboard retry after a forced 403, retained results on failure,
  compact/full Help and Escape/focus return. No jQuery script assets on these pages.
- Final scoped PHP log scan clean. Earlier fixture filesystem ownership was repaired;
  two exercised dynamic properties and a null user-ID trim were fixed in source.

Context Statistics uses the retained API but was not enabled or revived for this
batch. Narrow-viewport rendering has not been independently verified. No production
deployment is part of this change. Global jQuery remains enabled until the remaining
shared callers and supported modules have completed the same migration/testing gate.

Separately, Formbuilder is preserved as `formbuilder__PAUSED` in the modules repo
following fresh zero-registration/table/stored-reference results locally and across
all six production sites. Its archived libraries are intentionally still preserved.
