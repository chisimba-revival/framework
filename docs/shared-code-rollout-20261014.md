# Shared-code production rollout — 14 October 2026

Derek authorised synchronising all new shared code across all six existing sites.
The runtime pins are framework `26ec58356870aba4e60b6b27b681fb5b69ac4a6a`
and modules `9dfc7326ad713089ccb076c52f0be78cfa41f413`, on
`release/consolidation-20260930`. Later documentation-only commits do not change
these runtime pins. All sites retain PHP 8.5.4 and PDO MySQL.

This release includes the recent dependency retirements, native UI migrations
and Calendar security changes. Site configuration, branding, content and existing
module selections remain site-specific. Absent or paused modules were not enabled.

## Source reconciliation and corrections

The complete tracked runtime inventory was compared with each deployment, rather
than only comparing the recent diff. Application-code differences matched Git
history. Useful contentblocks regression coverage from deployed test variants was
reconciled into modules `9dfc7326a`; older Site Pages test variants are superseded
by canonical tests, including legacy `hide_title` compatibility.

The first Chisimba.com switch exposed an embedded-login call to removed
`oauthDisp()`. The previous code was restored and its homepage verified. No
database rollback was performed. Framework `26ec58356` removes the unused
provider calls, updates Security to 3.115 and adds an installed-runtime login
renderer regression. The corrected release was rehearsed and deployed successfully.

A KengaPub clone rehearsal detected that the old patch updater seeds missing
sample content/default discussion data. Production was unaffected. This rollout
uses the normal modulesadmin registration-update path with pre/post-install hooks,
and rejects pending SQL XML schema patches for explicit review. Updates were
repeated in each clone and protected content remained unchanged.

## Validation

- Candidate PHP files were linted using each production image. Each site's database
  was rehearsed in an internal-only Docker network with isolated uploads/sessions.
  Home, sign-in and recovery HTTP checks passed in the corrected rehearsals.
- Protected business-table fingerprints matched before/after updates, both in
  rehearsals and during stopped-writer production windows. Catalogue/language
  metadata changes are intentional; this does not claim every table was unchanged.
  Existing custom navigation and registered module sets were preserved.
- Dependency-retirement, HTML cleaner, native context editor, profile, Calendar,
  Site Pages and contentblocks focused PHP/JavaScript checks passed. The installed
  embedded-login renderer test passed locally and in production verification.
- One pre-existing static test remains stale:
  `app/core_modules/security/tests/login_failure_prg_contract_test.php` expects
  `checkIfRegistered('systemmanagement')`, absent from the current native login
  routing. Its structural assertion failed; this rollout does not claim a wholly
  passing repository-wide suite or exhaustive testing of every legacy module.
- Production homepage/sign-in/recovery HTTP checks passed on all six sites.
  Browser checks covered Chisimba.com home/embedded login; KengaLearn home,
  authenticated Kanban and native profile; Learn the Birds webinar management and
  reminder dashboard; KengaPub home and Calendar's anonymous login boundary;
  KengaSolutions home and authenticated Calendar, including quick/full Help and
  Escape; and Heritage Treazures home. No manual email, registration, purchase or
  destructive content action was performed.
- Full canonical source manifests passed for each site's present module set.
  Retired paths were absent. Application-log deltas contained no new warning,
  deprecation, fatal or exception lines at deployment verification. Configured
  mail timers were active after the switches.

Learn the Birds' dashboard still showed both morning reminders at 08:00 SAST
and the registered-attendee reminder at 17:30 for the 19:00 webinar on 15 October.
The Monday reminder remained complete. Viewing the dashboard did not send mail.

## Recovery records

Each site retains a verified compressed database backup, persistent-file archive,
previous release, exact image/Compose details and private operation evidence.
The backup directories below are under each site's root. Timestamps are UTC.
Never restore a database over subsequent user writes without a coordinated plan.

| Site | Site root | Backup directory | Protected tables |
| --- | --- | --- | ---: |
| chisimba.com | `/srv/chisimba-com` | `backups/shared-20261014-124546` | 107 |
| kengalearn.com | `/srv/kengalearn` | `backups/shared-20261014-124838` | 171 |
| learnthebirds.com | `/srv/learnthebirds.com` | `backups/shared-20261014-125401` | 109 |
| kengapub.com | `/srv/kengapub.com` | `backups/shared-20261014-125353` | 222 |
| kengasolutions.com | `/srv/kengasolutions.com` | `backups/shared-20261014-125715` | 218 |
| heritagetreazures.com | `/srv/heritagetreazures.com` | `backups/shared-20261014-125911` | 218 |

Active code is in the corresponding `releases/release-shared-STAMP/ch` directory.
Private operators/manifests and receipts remain at
`/srv/chisimba-shared-release-20261014` on the shared host and
`/srv/kengalearn/shared-release-20261014` on KengaLearn. Disposable rehearsal
database/config/stage copies are removed after successful verification; production
backups and releases are retained.

Existing stale catalogue records for curatedfaq, photogallery and spokenassessment
on the smaller sites were not enabled, deleted or recreated by this release.
They need separate catalogue housekeeping, not site-specific source forks.
