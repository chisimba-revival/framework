# PEAR retirement production rollout — 12 October 2026

Derek authorised deployment to all six existing Chisimba sites. The release uses
framework `82cdd0125` and modules `e8d2dfd2c`, both on
`release/consolidation-20260930`. The latter includes a deployment-audit correction
for older saved page-title preferences and an explicit media-import ownership
helper recovered from deployed source.

The deployable release removes the retired PEAR libraries, web test harness,
Module Catalogue archive uploader and unused Zend TAR adapter. Native mail,
configuration, events, translations and BBCode, the pinned getID3 update, and
shared control alignment are included. Paused Blog, Maillist and FOAF code is not
reactivated; their stored data is retained. SimpleBlog is unaffected by that pause.

The remaining database/XML compatibility libraries stay in place. Backend
selection and container images are unchanged: Chisimba.com, KengaLearn and
Learn the Birds use PDO; the other three sites still use MDB2. A future PDO switch
for those sites requires image preparation and a separate rehearsed switch.
Do not delete the entire PEAR directory based on this release.

The later authorised [PDO switch](database-migration/remaining-sites-pdo-20261012.md)
completed those three backends on the same day. The backend column below records
the state during this earlier PEAR rollout; all six now use PDO.

## Checks and corrections

- All six code trees were compared with current committed source and Git history.
  Thirty-one unmatched source files on older sites were retrieved read-only.
  Older Contact Form variants are superseded by the shared implementation;
  previously duplicated canvas CSS already exists in the root skin. The import
  helper/audio documentation were reconciled into source. Site Pages honours
  legacy `hide_title` data unless the current `show_title` preference is present.
- The title-preference regression suite passed on the host and in local PHP 8.5.
  Site Pages contracts, Contact Form service/guard tests, PEAR retirement guards
  and Zend gzip round-trip checks passed. No live diagnostic email was sent.
- Each exact production image linted its effective candidate files. Each site's
  database was cloned into an internal-only Docker network with disposable
  uploads. Normal catalogue updates ran twice, followed by strict native
  configuration/translation/authentication/MFA/catalogue/retirement checks.
- Rehearsals caught missing supporting files on the smaller installations.
  Candidate bundles were completed and retested; Heritage Treazures also needs
  Notes as a declared dependency of installed Kanban. No failed candidate was
  deployed. Modules absent from a site are not partially copied or arbitrarily
  enabled.
- Consistent database dumps and persistent-file archives were created and tested
  during each stopped-writer window. Previous releases remain available. Existing
  protected business-table hashes matched before and after catalogue updates;
  registered metadata and new dependency tables are expected changes. The exact
  inherited metadata exclusions are in the recorded operator, so these counts
  do not claim every table was unchanged.
- Mail workers were coordinated using their existing locks. KengaLearn's initial
  timer-stop attempt failed before changing live code; its successful deployment
  used the wrapper's existing readable flock instead. No privilege changes were
  needed. Other active timers were resumed after deployment.

## Release locations

All stamps below are UTC. Each site retains its `backups/pear-STAMP` directory,
including the prior release path, exact image ID, Compose copy, compressed database
and persistent files, protected-data fingerprints and private operation logs.
Never restore a database over subsequent user writes without a coordinated plan.

| Site | Root | Stamp | Protected existing tables | Backend |
| --- | --- | --- | ---: | --- |
| chisimba.com | `/srv/chisimba-com` | `20261012-082026` | 107 | PDO |
| kengalearn.com | `/srv/kengalearn` | `20261012-082414` | 171 | PDO |
| learnthebirds.com | `/srv/learnthebirds.com` | `20261012-082732` | 109 | PDO |
| kengapub.com | `/srv/kengapub.com` | `20261012-083243` | 222 | MDB2 |
| kengasolutions.com | `/srv/kengasolutions.com` | `20261012-083610` | 216 | MDB2 |
| heritagetreazures.com | `/srv/heritagetreazures.com` | `20261012-083908` | 213 | MDB2 |

Release directories use `releases/release-pear-STAMP/ch`. Dated operators and the
common 1,337-entry manifest are recorded in the sibling dev-environment repository
under `tools/pear-rollout-20261012/`. The initial 1,323-entry manifest and the
byte-identical extension attestation for the first three sites are retained.
Private host rehearsal data is not included in either repository.

## Post-deployment verification

All six sites reopened successfully. Public homepage/sign-in/recovery HTTP checks
passed. Browser checks covered Chisimba.com homepage/recovery; KengaLearn
homepage, authenticated Site Administration, Communications and catalogue guidance;
Learn the Birds authenticated webinar management and reminder dashboard; KengaPub
homepage/books/contact pages; KengaSolutions homepage/contact form and its
Inbox/Spam/Trash administration; Heritage Treazures homepage and contact form.
The older hidden homepage-title preference remained effective on Heritage.

Final source/retirement checks and PHP application-log deltas passed with no new
warning, deprecation, fatal or exception lines. All configured mail timers are
active. Learn the Birds' dashboard showed the recent worker heartbeat, scheduled
Thursday morning/pre-event sends and the completed Monday reminder. No manual
send, retry, registration or payment was triggered during browser verification.

Public navigation was separately compared with the pre-release database backups
using isolated temporary databases, because the catalogue metadata exclusion list
includes the menu table. This checks semantic rows rather than regenerated IDs.
The supplementary auditor reads dump bytes without decoding unrelated legacy
binary data. It found one regression: Contact Form registration had removed
KengaSolutions' customised Contact link. The exact backup row was restored through
dbTable; the final comparison reports all four original links present and none
missing. The other five sites had no missing public navigation rows.

Framework `e662874d9` fixes the underlying catalogue behaviour. Normal updates now
preserve customised site navigation; explicit Restore Defaults still resets it.
Full PDO and MDB2 suites passed, including real database checks for repeated
updates and explicit resets. Toolbar 1.813 was applied locally and to all six
production sites. Each production update asserted all existing site menu rows
remained identical. The archived operators now also require navigation fingerprints;
this guard was added after the finding, not part of the original rollout.

The active releases are the follow-up directories
`ROOT/releases/release-navigation-STAMP/ch`, with fresh database backups at
`ROOT/backups/navigation-STAMP`. Original PEAR-release backups and persistent-file
archives remain retained.

| Site | Follow-up stamp (UTC) |
| --- | --- |
| chisimba.com | `20261012-085056` |
| kengalearn.com | `20261012-085157` |
| learnthebirds.com | `20261012-085311` |
| kengapub.com | `20261012-085355` |
| kengasolutions.com | `20261012-085432` |
| heritagetreazures.com | `20261012-085507` |

Final verification of all six active releases passed: source hashes, retired-path
absence, unchanged container images, running web containers and configured mail
timers. PHP application-log deltas contained zero new diagnostic lines.

One separate observation: Heritage's footer `/contact-us/` link did not display
the contact form during this smoke check; it redirects to the legacy Site Pages
slug. The direct `index.php?module=contactform` route renders correctly. No footer
configuration or web-server routing was changed in this release; that legacy
link should be corrected separately.
