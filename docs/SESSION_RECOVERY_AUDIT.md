# Session and draft recovery audit — 18 September 2026

## Release gate

**Open: Chisimba-wide draft safety is not yet certified.** This is a workflow audit,
not a search for the words “session expired”. Authentication, form-token lifetime,
permissions, transport failures and save confirmation are separate concerns.
A successful remembered login must retain normal permitted capabilities; stale
form credentials must be renewed without accepting an unverified mutation.

For each editable workflow test: >30-minute-old form; eviction by opening 13+
forms; active login with rejected token; expired PHP session with valid Remember
me; fully signed out; re-login as same and different user; revoked permission;
network failure before and after possible commit; server/validation failure;
reload/back/navigation; successful cleanup; edits during save; simultaneous tabs;
blocked browser storage; rich-editor synchronisation; upload recovery limits.
Use disposable data and isolated accounts, not the user's active browser session.
No automatic uncertain-write replay, no weakened CSRF and no paid test requests.

## Deployment/source evidence

Base framework branch: `fix/installer-deprecations-20260917`.
Base modules branch: `feature/offline-mcq-workshop`.
Recovery work is isolated on `fix/session-recovery-20260918` in both repositories.
Active Docker mounts inspected: framework core/classes/skin and modules from these
checkouts, PHP 8.5 web runtime. Local installer updated htmlelements 0.617, security 3.109, toolbar 1.806 and
Kanban 0.121 normally. No production writes performed in this audit.

Read-only SSH check of KengaLearn's running container on 18 September:
Kanban **0.119**, missing local 0.120 inline-create protection. Production already
uses session-bound tokens, so a simple 15-minute token timeout is not sufficient
to explain that incident. Token eviction or loss/replacement of the PHP session
remain plausible; the incident itself has not been reproduced from server logs.

## Authentication finding and repair

The new persistent-login foundation issued and rotated remembered credentials,
but had no production caller for restoration. This was a gap in the new code,
not a legacy compatibility requirement. The engine resumed PHPSESSION only;
user::isLoggedIn checked NativeSessionService. Component tests did not cover a
lost-session browser journey.

Implemented a request-entry RememberedLoginResumptionService through the canonical
composition, before maintenance and module access decisions. It validates the
current active account, checks current MFA policy, rotates credentials/session ID,
and establishes identity only. Ordinary permissions continue to resolve through
canonical services. Current MFA challenge requirements cannot be waived by the
existing cookie schema (which has no assurance claim); such users must sign in
again. MFA challenge/enrolment UX after session loss still needs its own browser matrix.

Repaired logout renewal too: a long-lived toolbar had an old-session token even
after the editor restored authentication. Same-origin, account-bound preflight
renews its token; logout remains POST/CSRF protected. Invalid old remembered-cookie
responses no longer expire a replacement issued by a parallel request. Rotation
uses the configured credential expiry, rather than a hard-coded browser expiry.

Real isolated local browser PASS: normal password login with Remember me checked;
remove only that test browser's PHP session while keeping its remembered cookie;
submit an already-open board form; repeat with an already-open task form; verify
both saves, rotated cookie, rejection of old CSRF and logout revocation. User's
session untouched. Test account disabled, instructor membership removed, persistent logins
revoked and all disposable boards/tasks removed afterwards. Synthetic tests also
cover inactive/missing users, MFA-required policy, failed session regeneration,
token-pool eviction and fresh-token recovery. No deployment yet.

Open authentication checks: concurrent real restoration requests, interactive MFA,
and each other module's course/scope restoration. No claim that every application
form now handles a lost PHP session merely because identity can be restored.

## Workflow register

| Workflow | Evidence / risk | Status |
| --- | --- | --- |
| Kanban create task | 0.120 Ajax retains failed input, but no reload recovery; 0.119 production lacks it | 0.121 local recovery implemented; browser/HTTP-fixture checks pass |
| Kanban edit task/board, create board, subtasks, sharing | Full-page errors discarded fields; database sharing writes could report success after failure | Local Ajax failure retention, opt-in recovery, fresh-token preflight/account binding; sharing rollback added |
| Kanban move/toggle | Optimistic UI could falsely suggest saved state | Movement now waits for success; rejected checkbox restores prior state |
| Essay writing/submission | Draft Ajax exists; final submit sets dirty=false before response and invalid token redirects to list | OPEN, high risk; rich-editor adapter and final-submit recovery required |
| Worksheet manual marking | __savestudentmark rejects CSRF by redirecting to index, without returned marks/feedback | OPEN, high risk |
| Worksheet learner answers | Normal POST forms, no verified expired-login recovery | OPEN, high risk; inspect actual answering route and answer ownership |
| Gradebook assessment plan/sheet | Invalid CSRF redirects to plan/sheet | OPEN, verify payload preservation and Ajax caller |
| SimpleBlog / composition | Blog input restored on controller token/validation errors; builder commands retain DOM on Ajax errors | Partial protection only; final publish/login boundary, rich text and reload recovery unverified |
| Course content | Dedicated expired-form preservation in controller | Partial protection only; auth redirect and rich-editor/browser failure unverified |
| Knowledge Map | Save rejection retains in-memory graph and error state | Partial protection only; reload recovery/create/share/import unverified |
| MCQ Generator / exam assembly | Token preflight and server-returned input; saved source/job checkpoints | Partial protection; unsaved review/exam selections and true logout/reload unverified |
| System management | Email Ajax draft handling and server draft support | Partial protection; notice/schedule, session replacement and uncertain mail dispatch unverified |
| Biography/profile | Biography tab draft helper | Partial protection; conflict, account switch, profile fields and upload navigation unverified |
| Discussion | Marking Ajax and CSRF-specific contexts | OPEN for authoring/replies and prolonged marking sessions |
| Assignments, rubric, webinar, marketing, remaining installed editors | No end-to-end recovery evidence yet | OPEN; inventory installed routes before claiming coverage |

## First-batch evidence

- Kanban existing task creation/controller contract tests pass.
- New controller tests: same-origin renewal, cross-site/wrong-account/GET rejection,
  stale edit and board tokens never reach persistence.
- Synthetic browser: signed-out preflight sends no mutation; edit survives failure
  and reload; other account cannot restore; connection failure is not replayed;
  rejected save retains text; confirmed save clears only the relevant recovery copy.
- Existing browser: duplicate-submit guard, task actions, counts/focus, token rotation,
  error retention, reload recovery and collapse/keyboard regression pass.
- Shared draft browser tests: server conflict requires review, scope isolation,
  edits during save remain dirty, blocked storage leaves editable text and warning.
- Sharing repository: failed replacement rolls back existing grants; success commits.
- Actual local browser: normal create-board and task save exercised with disposable
  data; remembered-session save/logout journey also passed; wider MFA/role/concurrency matrices remain open.

## Next work, in order

1. Extend the passing Remember-me integration to concurrent restoration and interactive MFA; validate explicit course scope for each consumer.
2. Essay final submission and worksheet learner/marking workflows.
3. Shared rich-editor/composition recovery and final publish paths.
4. Gradebook, discussion, maps, assessment generators and remaining author/admin editors.
5. Isolated deployment rehearsal, then narrowly deploy tested changes to the
   authorised target. Do not deploy unrelated MCQ development branch changes.

Acceptance requires concrete behavioural evidence for each row. A source patch,
a long session timeout, an Ajax endpoint or a passing login unit test is not a pass.

Two pre-existing structural tests were corrected to match canonical ownership:
login dispatch may retain only its UI return-to/failure state (not authentication
keys), and the shared toolbar owns the logout form instead of the minimal landing
page. Behavioural authentication/toolbar tests remain in force. No third-party
libraries were changed. Runtime log inspection found no new PHP warnings,
deprecations or fatal errors in the local verification window.

Kanban additionally rejects oversize text instead of silently truncating it.
Authenticated non-Ajax error responses show a copyable escaped recovery panel;
true unauthenticated interception without JavaScript remains outside that fallback.
The shared helper is tab-local recovery, not durable autosave across tab closure.

## Broader native-auth regression suite

The complete `tests/nativeauth/*test.php` suite now passes **39 of 39**.
The first run exposed three outdated test contracts, corrected with current
behaviour retained: ordinary user provisioning no longer assigns bootstrap
membership; guarded login requires abuse-protection evaluation (including a
rejection test); MFA encryption derives a purpose-specific key from the
installation master key. The MFA key adapter accepts an optional canonical key
provider for isolated testing; its default production key path and derivation
remain unchanged. Tests never read or replace the live installation key.
