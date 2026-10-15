# jQuery Form retirement rollout — 15 October 2026

All three jQuery Form copies and their unused loader references are removed from
all six live sites. [Dependency tracing and local tests](dependencies/form-plugin-retirement-20261015.md).
This retires the JavaScript plugin, not the Forms application module.

Runtime commits on `release/consolidation-20260930`:

- framework: `ffe6ebd87c3a60314a55bdc802f01438997aa0e9`
- modules: `599aa215aa657ebafde470066bf26b750dad182a`

No unexplained source drift was found. Six isolated rehearsals passed production
PHP lint, stale/mixed plugin selection tests, native wrapper regressions, repeated
catalogue updates, PDO/configuration/translation/authentication/MFA checks and
home/login/password-reset smoke tests. oEmbed and retired generator route checks
also passed where those modules exist. No dormant module was enabled.

jquerycore is now 0.017. Optional source versions: jquery 0.061, Forms 0.203,
ShortURL 1.010. Only jquerycore required an installed catalogue update.

| Site | Changed files | Canonical entries | Protected tables | Backup |
| --- | ---: | ---: | ---: | --- |
| chisimba.com | 13 | 19296 | 107 | `/srv/chisimba-com/backups/shared-20261015-073919` |
| kengalearn.com | 13 | 19296 | 171 | `/srv/kengalearn/backups/shared-20261015-073952` |
| learnthebirds.com | 13 | 19296 | 109 | `/srv/learnthebirds.com/backups/shared-20261015-074055` |
| kengapub.com | 9 | 13269 | 222 | `/srv/kengapub.com/backups/shared-20261015-074317` |
| kengasolutions.com | 9 | 13219 | 218 | `/srv/kengasolutions.com/backups/shared-20261015-074400` |
| heritagetreazures.com | 9 | 13193 | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-074444` |

Deployments used verified database/persistent-file backups, coordinated workers,
brief stopped-writer windows and protected-data fingerprints. Data, existing
navigation, registration and core/UI/theme choices were preserved. All full
canonical-source checks passed, retired paths are absent and checked PHP logs
contain zero new diagnostic lines. Worker timers are active. No mail job was
manually triggered. Browser homepage checks passed on all six sites with no
reported warnings/errors; local homepage and Personal Space also passed.

No live form submission or dormant-module workflow was exercised. Source tracing
found no application Form API calls; Validate's optional guarded resetForm hook
is not a hard dependency. Validate, application submission handlers and their
security checks were not changed by this release.

Disposable rehearsal copies/configuration/database clones were removed. Verified
backups and live/previous releases were retained. Evidence is in
`work/form-retirement-release-20261015` and `work/form-retirement-check-20261015`.

Validate is the sole remaining jquerycore catalogue plugin, with native validation
migration still required for its legacy caller modules. Other jQuery/UI and widget
dependencies remain separate work.
