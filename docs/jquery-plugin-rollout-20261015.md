# Optional jQuery plugin retirement rollout — 15 October 2026

Deployed [the bounded tooltip/Flexigrid retirement](dependencies/jquery-tooltip-flexigrid-retirement-20261015.md)
to all six existing sites. Framework runtime pin is
`e4c9028c2121ac0240018cabfe85218dd2212d9d`; modules remain
`f0ba1f3564fd09857dcdb052e751e37abd9d7873`.
All six sites now register jquerycore 0.012. No schema change was needed.

The full tracked source audit found no unknown drift. Each site received the same
33-entry delta: 29 deleted wrapper/asset files, updated registration/catalogue and
two new regression-test files. Retired plugin directories were explicitly pruned
from the active releases. Previous releases and backup archives retain recoverable
historical copies. No older jquery-module/module-bundled plugin copy was removed.

## Checks and outcome

- Local host and installed PHP 8.5 tests, installed skin/session rendering, browser
  validation/Form/native-tooltip behaviour and Personal Space smoke checks passed;
  details and limitations are in the linked implementation report.
- Each site passed an isolated database-copy rehearsal on its exact PHP 8.5.4
  image, including the standalone stale-session regression, PHP lint, repeated
  normal catalogue registration and HTTP home/login/password-reset checks.
- Protected table fingerprints, existing custom navigation, installed-module sets
  and the exact existing JQUERY_CORE/UI_THEME configuration values were preserved
  during rehearsals and live updates. No user session was cleared.
- Sequential switches used verified database/persistent-file backups, stopped web
  writers and existing worker locks/timers. No manual mail send or worker trigger
  was issued. No rollback was required.
- All six live HTTP smoke checks passed. Chrome rendered each homepage with no
  retired plugin URLs in script/stylesheet elements. Production browser checks
  were read-only; interactive validation tests used the disposable local fixture.
- Final source verification matched the full per-site canonical inventories:
  19,358 entries on chisimba.com/KengaLearn/LearnTheBirds; 13,309 KengaPub;
  13,259 KengaSolutions; 13,233 Heritage. These include absent retired paths.
- Retired paths were absent on every current release, all captured PHP log deltas
  had zero new diagnostic lines, and all previously active worker timers were active.

## Retained recovery records

| Site | Protected tables | Verified backup directory |
| --- | ---: | --- |
| chisimba.com | 107 | `/srv/chisimba-com/backups/shared-20261015-044608` |
| kengalearn.com | 171 | `/srv/kengalearn/backups/shared-20261015-044644` |
| learnthebirds.com | 109 | `/srv/learnthebirds.com/backups/shared-20261015-044727` |
| kengapub.com | 222 | `/srv/kengapub.com/backups/shared-20261015-044852` |
| kengasolutions.com | 218 | `/srv/kengasolutions.com/backups/shared-20261015-044932` |
| heritagetreazures.com | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-045012` |

Receipts record previous/current release paths, image/source pins, backup hashes
and protected-data fingerprints. Private evidence remains in
`work/jquery-release-20261015` locally, `/srv/chisimba-jquery-release-20261015` on the
shared host and `/srv/kengalearn/jquery-release-20261015` on KengaLearn. Disposable
rehearsals are cleaned after successful verification; live/previous releases and
verified backup archives are retained. This report is a documentation-only commit
following the runtime release.

Global jQuery/UI loading and six retained optional plugins are unchanged. Remaining
work is migration of the older module-specific callers, with their distinct copies
and dependencies traced before removal. This rollout does not claim total jQuery
removal or that every unrelated repository test passes.
