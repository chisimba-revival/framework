# Native oEmbed rollout — 15 October 2026

Native opt-in media previews and the approved retirement of legacy oEmbed
publishing endpoints are deployed to all six existing sites. The obsolete
provider classes, output templates and jQuery oEmbed assets are removed.
[Implementation and local checks](dependencies/native-oembed-20261015.md).

Runtime commits on `release/consolidation-20260930`:

- framework: `f8cb3c5db88086acbe126c65c07e2dd34ceb69d0`
- modules: `929f0828bac72de9dda08ebeac33b2ab205f19cb`

Full source auditing found no unexplained drift. All six isolated rehearsals
passed production-image lint, loader regressions, repeated catalogue updates,
PDO/configuration/translation/authentication/MFA/catalogue checks and home,
login and password-reset HTTP checks. Where the optional module exists, native
consumer regressions and actual publisher HTTP 410 / resolver POST checks passed.
Three existing Wall switch-label PHP 8.5 deprecations were corrected during
rehearsal before deployment. No dormant module was enabled.

Installed versions: jquerycore 0.015, Filters 1.569, Skin 0.212. Optional source
versions: oEmbed 0.086, Wall 0.076. Validate, Form and LiveQuery remain in the
jquerycore bundle; this does not complete all jQuery migration.

| Site | Changed files | Canonical entries | Protected tables | Backup |
| --- | ---: | ---: | ---: | --- |
| chisimba.com | 40 | 19317 | 107 | `/srv/chisimba-com/backups/shared-20261015-061145` |
| kengalearn.com | 40 | 19317 | 171 | `/srv/kengalearn/backups/shared-20261015-061309` |
| learnthebirds.com | 40 | 19317 | 109 | `/srv/learnthebirds.com/backups/shared-20261015-061336` |
| kengapub.com | 10 | 13272 | 222 | `/srv/kengapub.com/backups/shared-20261015-061610` |
| kengasolutions.com | 10 | 13222 | 218 | `/srv/kengasolutions.com/backups/shared-20261015-061704` |
| heritagetreazures.com | 10 | 13196 | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-061927` |

Each deployment used verified database/persistent-file backups, worker locks,
a brief stopped-writer window, protected-table fingerprints and preserved
navigation. Heritage's first attempt stopped at a busy worker lock before any
live switch or web-container stop. Its timer resumed; the worker finished and
the retry passed with all lock checks retained. No database rollback occurred.

All final canonical-source checks passed. Retired files are absent, checked PHP
logs have zero new diagnostic lines and all configured worker timers are active.
No mail job was manually triggered. Browser homepage smoke tests passed on all
six sites with no reported warnings/errors. The retired publisher notice was
also checked in local and production browsers. External provider playback was
not tested; local native preview, keyboard, Help, dynamic reply and narrow-layout
checks are described in the implementation report.

Disposable rehearsal code/configuration/database copies were removed; live and
previous releases and verified backups were retained. Operational evidence is
in `work/oembed-release-20261015`.
