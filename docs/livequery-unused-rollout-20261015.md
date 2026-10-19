# Unused LiveQuery rollout — 15 October 2026

The bounded [unused-copy cleanup](dependencies/livequery-unused-copies-20261015.md)
is deployed on all six existing sites. Two unused LiveQuery copies, an orphaned
news-block editor script and an obsolete Context controller backup are removed.
The LiveQuery 1.0.2 dependency of the old Grid/Boxy wrappers remains intact.

Runtime commits on `release/consolidation-20260930`:

- framework: `061823da536ff2850651621292cd16f209a6b26f`
- modules: `d7f59801a97bfef6e033962f192f4407f266f5b4`

The source audit found no unexplained drift. Six isolated rehearsals passed
production-image syntax checks, plugin stale-session regression, repeated normal
catalogue updates, PDO/configuration/translation/authentication/MFA/catalogue
checks and home/login/password-reset smoke tests. Where present, retired
publishing/generator routes and the native media resolver also passed regression.

jquerycore is now 0.016; optional jquery source metadata is 0.059. Only jquerycore
required catalogue updating. Context remains 2.057: its only removed file was a
stale backup, not executable controller code. No dormant module was enabled.

| Site | Changed files | Canonical entries | Protected tables | Backup |
| --- | ---: | ---: | ---: | --- |
| chisimba.com | 9 | 19299 | 107 | `/srv/chisimba-com/backups/shared-20261015-070305` |
| kengalearn.com | 9 | 19299 | 171 | `/srv/kengalearn/backups/shared-20261015-070342` |
| learnthebirds.com | 9 | 19299 | 109 | `/srv/learnthebirds.com/backups/shared-20261015-070444` |
| kengapub.com | 8 | 13271 | 222 | `/srv/kengapub.com/backups/shared-20261015-070659` |
| kengasolutions.com | 8 | 13221 | 218 | `/srv/kengasolutions.com/backups/shared-20261015-070743` |
| heritagetreazures.com | 8 | 13195 | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-070827` |

Every deployment used verified database and persistent-file backups, worker
coordination, a brief stopped-writer window and unchanged protected-table
fingerprints. Existing navigation, registration and core/UI/theme configuration
were preserved. The release script waits for inactive/failed worker service
state and allows up to 120 seconds for a busy flock before stopping web writers;
it still fails closed and never terminates a mail worker to acquire the lock.

All full-source manifest checks passed, retired paths are absent, and checked PHP
logs contain zero new diagnostic lines. Worker timers are active. No mail job was
manually triggered. Browser homepage checks passed on all six sites with no
reported warnings/errors. Local Personal Space was also checked without loading
LiveQuery; the retained legacy loader still emits its existing asset.

Disposable rehearsal copies/configuration/database clones were removed. Verified
backups and live/previous releases remain. Operational evidence is under
`work/livequery-release-20261015` and `work/livequery-check-20261015`.

Form and Validate are the remaining jquerycore catalogue plugins. This is not
complete retirement of LiveQuery or jQuery: the Grid/Boxy/Forms/ShortURL/CMS Admin
caller cluster is the next dependency boundary to migrate and review.
