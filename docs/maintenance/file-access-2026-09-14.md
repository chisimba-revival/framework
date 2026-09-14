# Course-file and submission access — 14 September 2026

## Correction to the original probe

The original `results.json` records an inconsistent first controller guard, not
proof of an end-to-end byte leak. Its downloader was a stub. The real downstream
folderaccess downloader already checked course membership. The earlier first
check nevertheless read numeric offsets from an associative folder and allowed
any logged-in user to reach that downstream handler.

## Local correction

`filereadpolicy` now supplies a shared record-based decision to ID delivery,
legacy secure path delivery, file information and previews. It resolves the actual
course from the file record, checks canonical membership/instructor/admin services,
and recognises published public courses. Explicit private file/folder flags and
hidden-file restrictions retain stronger protection. Personal private_all retains
its existing authenticated-sharing semantics. Assignment-linked records are
excluded from generic File Manager delivery: use the submission download route.
That route and its storage resolver now reject file IDs belonging to another
submission. Secure downloads resolve a registered record, validate the filename,
apply the shared policy and constrain the real filesystem path beneath secure
storage, including symlink resolution.

## Verification

- `policy-test.php`: 50 access-decision assertions.
- `probe.php` / `after-fix-results.json`: 24 actual-controller assertions, including
  wrong-attachment denial for all six identities; no PHP diagnostics.
- `download-test.php`: eight checks using the actual secure downloader and a
  disposable file: permitted bytes, denied access, unknown record, wrong filename,
  traversal, symlink escape, and storage-level attachment mismatch/empty ID.
- Modified PHP files pass syntax checks.
- `after-fix-public`: all four HTTP public journeys return 200, zero PHP warnings.

## Boundaries still requiring integration verification

These are local fixes, not deployed. Role services in the focused tests are
fixtures; a full real-account course/assignment HTTP matrix remains outstanding.
Private secure storage is outside the web root. Files remaining in public web
storage can bypass application guards through a direct static URL. Course
visibility transitions and pre-existing public copies/thumbnails need a storage
and web-server audit before claiming universal private-course file protection.
No production files, user submissions, memberships or course records were changed.

## Real-account HTTP testing completed

Five disposable accounts signed in through native authentication: submitter,
peer, outsider, instructor and Site Admin; anonymous requests used a separate
session. A disposable course used real canonical groups, identities and activity
grants. File and assignment fixture records were seeded locally, not uploaded
through the assignment form. Actual PHP routes delivered identifiable fixture
bytes; permission services were not stubbed.

`account-results.json` records 234 requests across private, public and re-private
with the peer's membership removed. Of these, 180 are download/byte decisions:
175 matched expectations and five demonstrated the same static-storage defect.
The other 54 are file-information/preview requests: none returned fixture bytes;
this is a negative disclosure check, not proof of successful visual previews.
`account-extra-results.json` adds six real attachment-substitution checks (all
refused bytes), plus six file-information responses.

Confirmed:
- Published public ordinary course files are available to anonymous visitors.
- Guarded private course files allow members, instructor and administrator;
  outsiders/anonymous are refused. Revoked membership takes effect next request.
- Submission bytes through Assignment are available to submitter, instructor and
  administrator, and denied to peer, outsider and anonymous, for public and
  private courses. Generic File Manager routes do not deliver submission bytes.
- Switching a requested attachment to an unrelated file does not return bytes.

**Unresolved defect:** the ordinary file in public web storage remains available
through its static URL for outsiders/anonymous while the course is private, and
for the revoked peer after returning the course to private. All five unexpected
byte deliveries are this defect. The application guard cannot mediate Apache's
static delivery. Do not claim complete course-file privacy or deploy this as a
complete fix until storage/routing and existing public copies are addressed.
This test does not establish the state of any production site.

Fixture setup initially used an invalid admission policy and incomplete activity
grants; these were corrected before the recorded final run. Those initial
assignment admission failures were test setup errors, not application findings.

Cleanup verified: course, assignment, submission, file records, physical files,
course groups and fixture memberships removed. All five sessions logged out and
accounts deactivated, including removal of the temporary Site Admin membership.
Inactive identity rows remain as audit fixtures. No real accounts, courses or
production data changed. See `account-cleanup.txt`.
