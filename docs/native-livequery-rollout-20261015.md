# Final LiveQuery retirement rollout — 15 October 2026

The [native dynamic event migration](dependencies/native-livequery-20261015.md)
is deployed on all six existing sites. The final LiveQuery runtime and loader
are removed, with no remaining runtime source loads/calls or LiveQuery files.
Boxy and jqGrid themselves remain; their dynamic button bindings are now native.

Runtime commits on `release/consolidation-20260930`:

- framework: `f3251a3864ab992f5eadfce08a3b9d4dff93730a`
- modules: `43b71d7adc86885bcb2f67078bdd6e49d8fb85b9`

No unexplained source drift was found. All six isolated rehearsals passed
production-image lint, actual native wrapper output tests, stale-plugin session
regressions, repeated catalogue checks and PDO/configuration/translation/
authentication/MFA checks. Home/login/password-reset smoke tests passed; oEmbed
and retired generator route regressions passed where those modules exist.

No registered module needed updating. Optional source versions: jquery 0.060,
Forms 0.202, ShortURL 1.009, CMS Admin 1.651. All remain unregistered on these sites.

| Site | Changed files | Canonical entries | Protected tables | Backup |
| --- | ---: | ---: | ---: | --- |
| chisimba.com | 13 | 19297 | 107 | `/srv/chisimba-com/backups/shared-20261015-072303` |
| kengalearn.com | 13 | 19297 | 171 | `/srv/kengalearn/backups/shared-20261015-072335` |
| learnthebirds.com | 13 | 19297 | 109 | `/srv/learnthebirds.com/backups/shared-20261015-072429` |
| kengapub.com | 7 | 13270 | 222 | `/srv/kengapub.com/backups/shared-20261015-072631` |
| kengasolutions.com | 7 | 13220 | 218 | `/srv/kengasolutions.com/backups/shared-20261015-072715` |
| heritagetreazures.com | 7 | 13194 | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-072755` |

Each deployment used verified database/persistent-file backups, worker locks,
a brief stopped-writer window and protected-data fingerprint comparisons.
Existing navigation, registration and core/UI/theme configuration were preserved.
All final canonical-source checks passed; retired paths are absent. Checked PHP
logs contain zero new diagnostic lines and configured worker timers are active.
No mail job was manually triggered. Browser homepage smoke checks passed on all
six sites with no reported warnings/errors.

Local browser testing used actual wrapper-generated scripts with harmless
Boxy/grid recording stubs, covering nested clicks, keyboard activation, inline
output, inserted/replaced controls firing once, hostile-looking quoted values,
refresh targeting and non-bubbling focus. It did not exercise legacy write routes
or certify dormant modules' full workflows. Grid multiple-row deletion behaviour
is unchanged and needs review before any module revival. Native DOM binding does
not support jQuery-only synthetic events/namespaces; traced callers use click.

Disposable local browser fixture and remote rehearsal code/configuration/database
copies were removed. Backups and live/previous releases were retained. Operational
evidence remains under `work/native-livequery-release-20261015`.

Form and Validate remain in the jquerycore catalogue. Global jQuery/UI, Boxy,
jqGrid and other legacy plugin families are separate remaining dependencies.
