# Native Corner rollout — 15 October 2026

The jQuery Corner/Cornerz migration is deployed to all six existing sites.
[Source changes and local tests](dependencies/native-corners-20261015.md) describe
the bounded scope; the old CMS remains unregistered and was not revived.

Runtime commits on `release/consolidation-20260930`:

- framework: `4404c2cabb09d98f60ace7b1754dadd003d9bffb`
- modules: `1bd4dd7a5d106b299613728fd491e4a1fac037c1`

The full production audit found no unexplained source drift. All six isolated
rehearsals passed syntax and loader regression checks, normal catalogue updates
twice, native PDO/configuration/translation/authentication/MFA/catalogue checks,
and home/login/password-reset HTTP smoke tests. Existing data, editable
navigation, module registration and jQuery core/theme selections were preserved.

Installed updates: jquerycore 0.014, htmlelements 0.621, skin 0.211. Optional
jquery and CMSAdmin source metadata is 0.058 and 1.650 respectively; neither was
enabled. Four jquerycore plugins remain: Validate, Form, LiveQuery and oEmbed.

| Site | Changed files | Full source entries checked | Protected tables | Backup |
| --- | ---: | ---: | ---: | --- |
| chisimba.com | 16 | 19312 | 107 | `/srv/chisimba-com/backups/shared-20261015-052715` |
| kengalearn.com | 16 | 19312 | 171 | `/srv/kengalearn/backups/shared-20261015-052756` |
| learnthebirds.com | 16 | 19312 | 109 | `/srv/learnthebirds.com/backups/shared-20261015-052823` |
| kengapub.com | 13 | 13275 | 222 | `/srv/kengapub.com/backups/shared-20261015-052941` |
| kengasolutions.com | 13 | 13225 | 218 | `/srv/kengasolutions.com/backups/shared-20261015-053027` |
| heritagetreazures.com | 13 | 13199 | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-053107` |

Each deployment used verified database/persistent-file backups, worker locks or
timer coordination, a brief stopped-writer window and protected-table fingerprint
comparison. Final source hashes matched on every site, retired assets were absent,
and checked PHP logs contained zero new diagnostic lines. All configured worker
timers were active. No mail job was manually triggered.

Browser homepage smoke tests passed on all six sites with no reported browser
warnings/errors. The local fixture rendered the actual panel method using real
HTML helpers and synthetic read-only permission/configuration services; all 24
icon/link elements received native 8px radii without a Corner script. This does
not claim full dormant-CMS workflow or security testing.

Disposable local fixture and remote rehearsal code/configuration/database copies
were removed. Previous/live releases and verified backups were retained. Private
operational evidence remains in `work/corner-release-20261015`.
