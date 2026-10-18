# Personal Space editor repair — 14 October 2026

Local-only repair authorised after the dependency cleanup exposed a regression:
Personal Space still included Context's block script, but the earlier native
Context migration required settings and a course-specific JSON boundary that
Personal Space did not provide. A page-load smoke check had not detected this;
the editing toggle did nothing. Its older direct removal route also deleted by
placement ID without checking ownership.

Personal Space 1.013 now supplies the native editor settings and an explicit
Personal Space endpoint. Every preview/write requires an authenticated user,
POST and a shared owner-bound CSRF token. The owner is derived from the session,
never a submitted user ID, with no cross-user administrator shortcut. Preview/add
accept only offered site/user blocks, the user's correctly sized dynamic blocks
from registered modules, or the existing offered Content blocks.

Context 2.057 and Blocks 0.538 share the existing transactional placement algorithm
through `blocks/blockplacementorder`. Both repositories use dbTable, revision
checks, a shared lock and checked savepoints. A foreign placement ID cannot be
moved or removed; an ordering failure rolls back all writes. Obsolete Personal
Space mutation methods were removed after finding no external source callers.
Stored placements whose source is unavailable remain removable placeholders;
their source content is not executed or deleted.

The browser controls retain selections on definite rejection and stop further
editing after a stale or uncertain write. Addition reloads only after a confirmed
save. Removal confirms in an HTML dialog with Cancel initially focused and Escape
cancellation. The compact editing/Help toolbar uses shared skin primitives;
pickers have labels, movement uses native buttons, and the editing toggle exposes
its pressed state. The wide dynamic-block picker now uses the correct collection.
New contextual Help explains ownership, immediate saving and recovery.

The unused LiveQuery request and commented Worksheet form-plugin fragments were
removed. Global jQuery loading and other modules' plugin assets remain intact.

## Local verification

- Changed PHP syntax, JavaScript syntax and Git whitespace checks passed.
- Personal Space request tests passed: anonymous, GET, invalid/expired CSRF,
  malformed side/direction, foreign dynamic block, wrong size, disabled module,
  submitted victim ID ignored, and repository ownership rejection.
- Actual PHP 8.5/PDO tests passed for Personal Space and Context: add/move/remove,
  cross-owner/course denial, stale revision, injected second-write failure,
  nested rollback and outer rollback. Unavailable placement placeholders retained
  the layout without rendering source blocks. Synthetic database rows rolled back.
- Shared client tests passed for both endpoint configurations: stale previews,
  duplicate submission prevention, token renewal, retained selection and no replay
  after an uncertain write. Existing Context permission/Help/landing-route and
  Worksheet Help tests passed.
- Chrome local browser: preview, add with reload, move up/down, keyboard movement,
  Escape cancellation, confirmed test-placement removal, stale save rejection
  following a simulated concurrent fixture write, reload recovery, quick/full
  Help and Escape. Desktop and 390-pixel checks passed without horizontal overflow.
- Both disposable dynamic blocks and their placements were removed. Original
  Personal Space placement rows matched the captured baseline exactly. The
  original course page also rendered correctly after the shared changes.
- Captured browser console warnings/errors were empty. The final application-log
  delta contained no new PHP warning, deprecation, fatal or exception lines.

Normal catalogue updates completed locally, including repeated updates. No schema
change, account edit, manual email, production deployment or production data
change occurred. Framework and modules changes must ship together; local test
evidence/screenshot are in `work/personalspace-repair-20261014` in the parent
workspace. Test entry points and fixture rules are in Personal Space's tests README.
