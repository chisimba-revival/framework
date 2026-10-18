# Native course block editing — 14 October 2026

Context 2.056 replaces the active `contextblocks.js` jQuery caller with native DOM,
fetch and an HTML confirmation dialog. Its unused pre-migration backup script is
removed. Global skin jQuery remains for other consumers; Calendar is the next
registered caller in the wider migration plan.

The request boundary now checks the authenticated current course, current
administrator/instructor role, POST and a course-bound shared CSRF token. Preview
and add accept only registered catalogue blocks, dynamic blocks belonging to the
current course, or the existing site Content blocks offered by the picker. The
remove action can no longer delete a placement from another course by ID.
Unsupported directions and sides are rejected.

The repository uses dbTable prepared reads, a shared database lock and checked
transactions/savepoints. A page revision rejects stale layouts, including changes
made in another tab. Revision capture precedes rendering, so a concurrent change
during rendering cannot silently legitimise an older displayed layout. Moves
renumber the selected side deterministically and roll back on a failed write.
The change adds no schema and uses no direct driver calls.

Move and remove update the page only after a confirmed save. Add reloads after a
confirmed save so arbitrary module blocks initialise through the ordinary page
lifecycle; preview HTML does not execute embedded scripts. Preview responses are
ordered, duplicate saves are blocked and CSRF rejection retains the selection.
An interrupted/unknown write or stale revision disables further editing until
reload, without replaying the request. Removal confirms in an HTML dialog with
Cancel initially focused, Escape cancellation and focus return. Contextual Help
explains immediate saving, reload and recovery. Wide dynamic block options also
now populate the correct dropdown.

Validation completed locally:

- PHP/JavaScript syntax and Git whitespace checks.
- `native_block_editor_test.php`: method, CSRF, scope and role rejection;
  anonymous, student/outsider, instructor, revoked instructor and administrator;
  block allowlist and invalid side/direction; no writes on negative paths.
- `native_block_order_pdo_test.php` with `CHISIMBA_BLOCK_TEST=1` from the installed
  root: actual PHP 8.5/PDO add, move and remove; cross-course rejection; stale
  revisions; failure injected on the second ordering write; nested and outer
  rollback. All synthetic transaction records removed.
- `native_block_client_test.js`: stale preview responses, duplicate submissions,
  CSRF renewal with selection retained, interrupted write lockout and no replay.
- Existing context-home Help and landing-content route checks.
- Chrome against a disposable local course: editing mode, preview, add and page
  reload, move, confirmed remove, Escape cancellation, stale-layout rejection
  after a simulated concurrent writer, full Help and Escape, desktop and narrow
  viewport. No console errors or PHP warnings/deprecations in the final test window.
  The old browser confirm blocked automation; its in-page replacement passed.
- Disposable browser-test course and placements removed. Original course restored
  in the browser session. No existing course layout changed.

Module Catalogue update, hooks and language registration completed locally.
No production deployment.
