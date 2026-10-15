# PNG workaround and browser test builder rollout — 15 October 2026

The obsolete IE PNG-transparency workarounds and the retired browser test builder
are removed from local source and all existing production sites. Old builder
URLs return HTTP 410 on the three sites that carried its source; it remains
absent on the other three. Existing data and generated user files were preserved.
The PHPUnit CLI testing library is separate and was not removed.

Runtime source pins on `release/consolidation-20260930`:

- framework: `f98b8e4230d46e5a2ce2e9255a194f901e8f61ba`
- modules: `85ad27090fe6e69b37883358c5cb320ed35b8094`

The full six-site source audit found no unexplained differences. Isolated
rehearsals used each site's actual PHP image and database copy. They passed
syntax checks, plugin-loader regression, normal catalogue updates twice,
PDO/configuration/translation/authentication/MFA/catalogue checks, and anonymous
home/login/password-reset HTTP checks. The builder regression and actual HTTP
410 route were checked wherever its compatibility controller exists.

Only already-registered jquerycore (0.013) and userregistration (0.046) needed
catalogue updates. No dormant module was installed or enabled. Existing module
registration, editable navigation, business-table fingerprints and configured
jQuery core/UI theme were preserved. All sites retain core 1.7.2 and Base theme.

| Site | Release file changes | Full source entries checked | Protected tables | Backup |
| --- | ---: | ---: | ---: | --- |
| chisimba.com | 36 | 19332 | 107 | `/srv/chisimba-com/backups/shared-20261015-050902` |
| kengalearn.com | 36 | 19332 | 171 | `/srv/kengalearn/backups/shared-20261015-050946` |
| learnthebirds.com | 36 | 19332 | 109 | `/srv/learnthebirds.com/backups/shared-20261015-051013` |
| kengapub.com | 12 | 13280 | 222 | `/srv/kengapub.com/backups/shared-20261015-051138` |
| kengasolutions.com | 12 | 13230 | 218 | `/srv/kengasolutions.com/backups/shared-20261015-051220` |
| heritagetreazures.com | 12 | 13204 | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-051301` |

Each switch used verified database and persistent-file backups, coordinated
worker locks/timers, a brief stopped-writer window and protected-data comparison.
All configured worker timers were active at final verification. No mail job was
manually triggered.

All final source hashes matched, retired paths were absent, and there were zero
new PHP diagnostic lines in the checked application logs. Browser homepage smoke
tests passed on all six sites with no reported browser warnings/errors. Local
browser checks also verified native transparent PNG rendering, retained Form and
Validate behaviour, and the inert generator route.

Disposable local browser fixtures and remote rehearsal copies/configuration/
database dumps were removed. Live releases, previous releases and verified
backups were retained. Private operational evidence is in
`work/pngfix-release-20261015`; the implementation and dependency checks are
recorded in [the source report](dependencies/pngfix-retirement-20261015.md).

This completes this bounded slice. Five jquerycore plugins remain: Validate,
Form, Corner, LiveQuery and oEmbed. Their callers and other jQuery module copies
still require migration; global jQuery and jQuery UI have not been removed.
