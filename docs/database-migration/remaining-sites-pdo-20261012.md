# Remaining sites: PDO deployment — 12 October 2026

Derek authorised switching KengaPub, KengaSolutions and Heritage Treazures after
the six-site PEAR cleanup release. Their application code already contained the
native PDO implementation. The remaining prerequisite was the MySQL PDO driver:
the shared PHP 8.5.4 parent image exposed only the SQLite PDO driver.

## Change and preservation boundary

A derived image adds `pdo_mysql` to the exact running parent, retaining mysqli and
all other existing extensions. Each site's web Compose image selects
`chisimba/pdo-remaining:20261012`; `config.xml` selects `DATABASE_ABSTRACTION=PDO`.
The immutable candidate image is
`sha256:823f958f1919fa220581a40db8188828c0b7f7626d3f699122a0f55680f8e9ad`.

No application source files, database schemas, credentials, content, uploads or
release paths change. Database containers are not recreated. No catalogue update
is needed. Existing release paths retain the PEAR cleanup plus framework
`e662874d9` navigation fix and modules `e8d2dfd2c` from the preceding rollout.
Remaining PEAR files are retained for the subsequent dependency audit.

## Rehearsal and contract evidence

- Each live database was copied into a separate internal-only Docker network with
  disposable tmpfs storage. Copied configuration selected PDO and the clone DSN;
  uploads and sessions were disposable, with no live file mounts or external mail.
- Strict runtime verification ran twice on each clone: actual native PDO
  composition, configuration, translation, authentication, MFA and catalogue.
  Every existing table was fingerprinted before and after, with no exclusions:
  KengaPub 249 tables; KengaSolutions 245; Heritage Treazures 245. All matched.
- Full PDO contracts ran on each site's staged code and the candidate image in
  separate synthetic databases. Binding/value parity, transactions, rollback,
  permissions, navigation, concurrency, repositories, schema/catalogue operations,
  engine composition and all 107 installer definitions passed without warnings.
- KengaSolutions' initial contract run stopped because the shared suite requires
  Shop, which is not installed on that site. Shop from modules `e8d2dfd2c` was added
  only to a separate disposable fixture tree. A nested read-only bind mount was
  initially rejected by Docker; the separate fixture tree resolved the harness
  issue. Neither failure touched live data or installed Shop on a production site.

## Deployment safeguards

Each switch rechecks code, configuration and image identities. Mail timers are
stopped where configured, running work is drained and existing worker locks are
held. Only the target web container is stopped. Complete compressed database and
persistent configuration/uploads/session backups are verified before changing
configuration. Strict PDO verification and all-table hashes must pass before web
reopens. Source files and unrelated configuration are checked again afterward.

Backups retain the original Compose file, exact old/new image IDs, original
`config.xml`, verified database dump and persistent-file archive. A rollback should
coordinate web/worker suspension and revert image/configuration while preserving
subsequent user writes. Do not automatically restore the database backup.

The dated operators are in the sibling dev-environment repository at
`tools/pdo-remaining-20261012/`. Private rehearsal data and receipts remain under
`/srv/pdo-remaining-20261012` on the shared host; none are committed.

## Completed deployment and browser acceptance

All three sites reopened on native PDO. The same all-table fingerprints matched
before/after their stopped-writer switch, not just during rehearsal.

| Site | Backup directory | Tables unchanged |
| --- | --- | ---: |
| KengaPub | `/srv/kengapub.com/backups/pdo-20261012-091339` | 249 |
| KengaSolutions | `/srv/kengasolutions.com/backups/pdo-20261012-091656` | 245 |
| Heritage Treazures | `/srv/heritagetreazures.com/backups/pdo-20261012-091808` | 245 |

Browser checks covered KengaPub's homepage/books/prices, empty cart, sign-in and
contact form; KengaSolutions' homepage, retained Contact navigation, contact form
and authenticated Contact messages administration; Heritage's homepage, sign-in,
password-recovery and direct contact form. KengaSolutions' existing administrator
session survived. Browser diagnostic checks returned no warnings/errors. No form,
message, order, payment or password-reset request was submitted.

These checks cover public routes and the existing KengaSolutions administrator
session, not fresh password/MFA browser challenges on every site. Authentication,
MFA and transaction behaviour have separate strict runtime and synthetic tests.
Heritage's previously recorded footer Contact slug issue is unchanged and separate
from PDO; the direct contact-form route works.

Final checks after browsing confirmed actual PDO composition, the exact candidate
image, unchanged source/unrelated configuration and zero new PHP application
warning/error lines. KengaPub and Heritage mail timers are active; KengaSolutions
has no configured mail timer in this deployment. Disposable databases and networks
were removed after their test runs.

Together with Chisimba.com, KengaLearn and Learn the Birds, all six production sites
now select PDO. This removes the live-backend blocker to further MDB2 retirement,
but does not establish that every dormant module or remaining XML/vendor caller is
independent of PEAR. Those dependencies require explicit clearance before deletion.
