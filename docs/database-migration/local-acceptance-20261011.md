# Local PDO acceptance — 11 October 2026

Subsequent authorised production work is recorded in
[the staged rollout report](production-rollout-20261011.md). The local-stage
statements below describe the state at that earlier acceptance checkpoint.

The three authorised stages are complete for the installed local MySQL/MariaDB
site: remaining active module callers, shared catalogue/installer schema work,
and local PDO activation with browser acceptance. This is not a production
release or approval to remove every bundled PEAR package.

## Result and boundaries

- `chisimba.test:8445/ch/` now uses `DATABASE_ABSTRACTION=PDO`.
- The actual installed engine reports `ChisimbaDatabaseConnection`; MDB2 is not
  loaded. Authentication/MFA/remembered-login/abuse composition passes on PDO.
- PHP 8.5.11 has `pdo_mysql`; MariaDB is 10.11.18. The old image and MDB2 source
  remain available. No production site, Git commit or GitHub push was changed.
- Framework and modules working branches remain `release/consolidation-20260930`.
  Changes span framework, modules and the local PHP image configuration.
- A private consistent local database dump and original configuration were kept
  outside the source repositories. The dump was restored into an internal-only
  Docker network with no published ports, where destructive/failure/concurrency
  scenarios ran with fixture email/payment/AI boundaries.

## Persistence and schema

Kanban, Knowledge Maps and Question Generator now use dbTable prepared queries
and checked shared transactions. The caller audit also led to migration of active
registration, membership, entitlement, account-event, legal-acceptance, payment,
notes, discussion, announcements, notifications, certificates, content, publishing,
events, shop, host, webinar and MCQ-bank persistence and relevant install hooks.
The audit of registered application code found no remaining raw driver caller;
remaining signals were reviewed shared-service references/configuration, not
connections. Static results are candidate evidence, not a complete call graph.

The shared schema service supports the catalogue's table, column, index,
constraint, alteration and database operations. DDL refuses active transactions;
alteration checks validate without writing. Logical index names preserve MDB2's
physical `_idx` convention, preventing duplicate upgrade constraints.
Fresh installation uses the same native connection and schema machinery and
checks `pdo_mysql`. Its configuration defaults to PDO. PostgreSQL and other
historical drivers are outside this MySQL acceptance boundary.

Legacy dbTable CRUD/prepared APIs preserve empty-string-to-SQL-NULL conversion,
string rows, case folding and trailing-space behaviour. Exact prepared APIs
preserve empty strings, Unicode, JSON and SQL NULL separately. Storage-level NULL
checks were added after course creation exposed a gap that display-only parity
would not detect. Events/Shop preserve their previous empty-value write contract.

## Checks actually run

- Full disposable MDB2 and PDO suites: CRUD/storage parity, bound hostile values,
  exact text, checked transaction ownership/savepoints, rollback and disconnected
  commits, shared locks, security token/MFA races, ACL/toolbar and classification.
- Both real drivers: Kanban grants/revocation and forced-write rollback,
  task/subtask edits, map revision/graph rollback, exam/set owner isolation,
  stale edits, deletion guards and idempotent imports.
- Schema columns compared against a real MDB2 DDL oracle; invalid/dry-run and
  transaction-guard checks. Native installer created 107 schema definitions and
  round-tripped bound seed data without loading MDB2. This was schema/seed
  integration, not a complete browser walkthrough of a fresh installation.
- Actual catalogue `makeTable()` repeated, plus legacy Shop upgrades repeated
  with unchanged product data and index definitions, on both drivers.
- Standalone Shop fresh/legacy upgrade harness passed after adapting its test
  boundary to dbTable.
- Restored PDO installation: authentication composition, MCQ bank permissions,
  imports, duplicates, course deletion, revocation and rollback; registration
  creation, protected states, token invalidation, expiry, redaction and audit.
- Restored installation on both PDO and MDB2: Events last-seat concurrency,
  callback replay, VAT, refunds, transfers, check-in and waitlists; Shop last-copy
  concurrency, payment/dispatch/refund state and rollback; Contact preview,
  quarantine, repeat submission, trash/restore and block/unblock. No mail/payment
  provider or AI requests were made. PDO Events editor/host contracts also pass.
- 127 changed/new PHP/INC files passed syntax checks at the final broad pass;
  subsequent small test-only edits were checked separately. 125 focused module
  scripts pass. Two unrelated contextcontent source-string assertions fail also
  on committed HEAD: `contextcontent_ingest_browser_contract_test.php` (expects
  hard-coded “Open course content”, while the register uses configurable
  “Open [-context-] content”; the expected button markup is present) and
  `contextcontent_rebuild_contract_test.php` (expects a textinput helper call,
  while native HTML retains the correct chaptertitle field name).
  They are recorded failures, not silently treated as passes.
- Scoped normal catalogue updates for Kanban, Knowledge Maps, Question Generator,
  Toolbar and Shop rehearsed twice, then applied locally on MDB2 before switching.
  Protected board/map/exam/question/account data checksums were unchanged.
- Browser on the actual PDO site: create exam, save/reopen source without AI;
  create Kanban board/task/subtask, complete/rename subtask, move task, reload;
  create/edit/save/reload a Unicode map and child node. Read-only checks include
  administration, registration protection, products/prices, notes, pages,
  certificates, navigation, catalogue, Shop, contacts, live sessions and banks.
- Browser console contained only Grammarly extension warnings/errors, not app
  errors. PHP dynamic-property/session and search warnings remain. The runtime
  handler labels all intercepted non-fatal warnings “Suppressed legacy PHP
  warning”; that label does not establish their age. Comparing the actual browser
  interval against the retained pre-task log (starting 29 September), with trailing
  source line numbers removed, finds 176 of 227 distinct warning signatures in
  that earlier log. The other 51 are not established as pre-existing by that
  comparison and require investigation; different route coverage can also account
  for previously unseen signatures. The September framework and module warning
  cleanup commits are still ancestors of the current HEADs.
- Only this run's explicit browser fixture IDs were removed in a checked
  transaction. All 11 protected table checksums then matched the restored
  pre-migration backup exactly, including canonical accounts.

## Limits and remaining work

Kanban's existing utf8mb3 schema rejects four-byte emoji. The browser retained the
failed draft; quoted and accented text saved after correction. This was not
silently turned into a schema/encoding migration. Knowledge Maps and Question
Generator round-tripped emoji successfully.

Browser acceptance used the existing authenticated session. A fresh password/MFA
browser challenge was not performed; the shared authentication database/factory
and security lifecycle contracts were exercised separately. Unresolved PHP
warnings and the two baseline source-string tests mean this is not a claim that
every legacy test or warning across the project is clean.

Uninstalled legacy modules (including Audience's remaining direct caller and the
external Blog Importer), vendor stores and operator-only MDB2 APIs still need
review before enabling them on PDO or deleting MDB2. Legacy XML schema reversal,
dump and sequence APIs deliberately reject native use; no caller should mistake
that for a successful backup. Use MariaDB's native backup/restore tools.

Next release work: review the coordinated uncommitted changes, reconcile the
actual target site's module inventory/source drift, ensure its exact PHP image
has pdo_mysql, back up and rehearse that site's updates, then begin production
with chisimba.com. KengaLearn remains excluded from migration testing. Remove
obsolete PEAR/MDB2 code only after the remaining dependency audit and fallback
retirement. Language-administration UI is a separate outstanding feature.


## PHP 8 warning cleanup follow-up (11 October 2026)

This follow-up supersedes the warning and stale-test limitations recorded above.
The two course-content tests now pass. The import test checks configurable
terminology and parses the permission action list instead of assuming it ends
immediately after the import actions. The chapter test checks the actual native
HTML field name. Broader testing also repaired obsolete database-boundary mocks
in the runtime-state and catalogue-hook tests.

Warning causes were fixed in the components that own them: explicit property
declarations, ordinary object assignments instead of obsolete references,
session INI configuration before cookie resumption, required-argument signatures,
null-safe empty form values/optional dates/search metadata, native database date
formatting, canonical casts, switch punctuation and CurlHandle lifetime. Existing
warning logging remains enabled; no AllowDynamicProperties attribute or new global
suppression was introduced. Session identifiers still come only from cookies.

Three bundled Zend Lucene files have explicitly marked local PHP 8 maintenance:
Field.php normalises absent metadata, Index/Writer.php uses an integer before
bitwise version encoding, and Storage/File/Filesystem.php replaces removed
track_errors/php_errormsg with a meaningful exception. Search encoding is retained;
Latin-1 conversion and UTF-8/emoji preservation have strict regression coverage.

Validation on PHP 8.5.11:

- 181 standalone module/core/authentication regressions pass with
  `tests/core/strict_warning_bootstrap.php` as auto_prepend_file. A separate new
  strict Lucene regression also passes (182 standalone checks in total).
  The complete standalone suite runs in a container with networking disabled.
  Paystack's contract now substitutes its transport explicitly, avoiding the
  old attempted provider lookup with fixture credentials. The native-login
  source contract uses PHP traversal and no longer requires rg in the image.
- 236 changed/new PHP files passed syntax and compile-time deprecation checks;
  the subsequently added search regression also compiled and passed.
- Real HTTP cookie-resumption and already-active-session tests pass with strict
  warnings, preserving the same cookie identity and sentinel state. Persisted
  outsider/student/instructor/revoked/administrator policies pass in the clone;
  their temporary users/groups are removed.
- Strict installed authentication/MFA/remembered-login/abuse and classification
  composition checks pass. Isolated catalogue/MCQ fixtures exercise course and
  chapter creation, search indexing, question-bank transfer, rollback, course
  deletion and grant revocation without warnings. Events editor/host and Contact
  runtime contracts also pass under strict warnings.
- Full disposable PDO and MDB2 database regression runners pass. The strict
  runtime warning acceptance uses PDO, the selected local backend.
- The final browser interval added **zero bytes** to the PHP error log. It covers
  home and administration, permissions, question banks, certificates/audit,
  Kanban, Question Generator, Knowledge Maps, products/prices, live sessions,
  notes, catalogue, Shop, contacts, empty/populated courses, chapter editor,
  document import and registration protection. The final browser console
  warning/error capture was empty. Course context was returned to the lobby.
- Git whitespace checks pass. No commits, pushes or production deployment.

Write-path acceptance used an isolated restore of the retained local backup,
with a private writable search index and no external network. The disposable
containers/network were removed afterwards. Diagnostic output is retained in
workspace killme.txt; historical failed discovery runs remain distinguishable
from the final passing checks.

This is a warning-free result for the tested PHP 8.5.11 workflows, not an assertion
that every dormant module or future PHP release has been validated. Fresh actual
password/MFA browser sign-in, the remaining MDB2 retirement inventory and the
Kanban utf8mb3 limitation remain the separate boundaries noted above.
