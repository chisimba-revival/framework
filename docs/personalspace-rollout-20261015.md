# Personal Space shared-code rollout — 15 October 2026

Completed the authorised six-site rollout of the [Personal Space repair](dependencies/native-personalspace-20261014.md).
Runtime pins: framework `a0c7d3c27a9d020dfc8b3ef3ddd92a1910f65d1b`,
modules `f0ba1f3564fd09857dcdb052e751e37abd9d7873`.
Both repositories were clean and matched their fetched GitHub branches before
preparation. This report is a later documentation-only commit.

The full tracked runtime inventory was compared with each deployment before the
release. No unrecognised source differences were found. All sites needed the same
18 changed files; the exact per-site module directory selection was preserved.
Context is now 2.057 and Blocks 0.538 everywhere. Personal Space is now 1.013 on
KengaPub, KengaSolutions and Heritage, where it was already registered. It remains
unregistered on chisimba.com, KengaLearn and LearnTheBirds. No module was enabled
as part of this rollout. Worksheet’s changes only remove commented plugin code.

## Validation and deployment

- Restarted the existing local PHP 8.5 containers after the power failure.
  Personal Space and Context actual-PDO tests passed, including stale revisions,
  foreign scope denial, injected write failure and nested/outer rollback.
  Personal Space permission tests, Context permission tests and both endpoint
  configurations of the shared JavaScript regression suite passed.
- Local Chrome confirmed Personal Space, editing controls and contextual Help
  after restart, with no captured console warnings/errors. Yesterday’s full
  disposable-fixture behavioural/browser checks are recorded in the repair report.
- Each candidate passed exact-image PHP lint and isolated database-copy rehearsal
  on PHP 8.5.4, including repeated normal catalogue registration with hooks,
  unchanged protected table hashes, unchanged existing custom navigation,
  native configuration/translation/authentication checks, and anonymous HTTP
  home/login/password-reset routes. No database schema change was required.
- Deployed sequentially: chisimba.com, KengaLearn, LearnTheBirds, KengaPub,
  KengaSolutions, Heritage. Verified database and persistent-file backups before
  each switch. Stopped web writers and coordinated mail/AI workers through existing
  timers/locks. No manual email send or worker trigger was issued.
- All protected table hashes and existing custom navigation matched before/after
  each live catalogue update. All public HTTP smoke checks passed. Chrome also
  rendered each live homepage, including LearnTheBirds’ webinar listing and the
  authenticated KengaLearn page with no pending catalogue updates.
- Final full canonical inventories matched: 19,356 entries on each of the first
  three sites; 13,307 KengaPub, 13,257 KengaSolutions, 13,231 Heritage. Each site’s
  18 changed-file hashes matched. No new PHP diagnostic lines were found in the
  captured log deltas. All previously active worker timers were active afterwards.

## Recovery records

| Site | Protected tables | Retained verified backup |
| --- | ---: | --- |
| chisimba.com | 107 | `/srv/chisimba-com/backups/shared-20261015-041614` |
| kengalearn.com | 171 | `/srv/kengalearn/backups/shared-20261015-041713` |
| learnthebirds.com | 109 | `/srv/learnthebirds.com/backups/shared-20261015-041758` |
| kengapub.com | 222 | `/srv/kengapub.com/backups/shared-20261015-041935` |
| kengasolutions.com | 218 | `/srv/kengasolutions.com/backups/shared-20261015-042051` |
| heritagetreazures.com | 218 | `/srv/heritagetreazures.com/backups/shared-20261015-042145` |

Each backup contains the release receipt, previous release path, Compose file,
verified database/persistent-file archives, operation log and protected-data
fingerprints. Previous code releases remain available. No rollback was required.
Private operational evidence is in `work/personalspace-release-20261015` locally,
`/srv/chisimba-personalspace-release-20261015` on the shared host and
`/srv/kengalearn/personalspace-release-20261015` on KengaLearn. Disposable rehearsal
copies can be removed only after their successful release receipts are verified;
retain live/previous releases and backup archives.

## Remaining work and limits

The next cleanup is bounded jquerycore tooltip/Flexigrid retirement, with
[production reference evidence](dependencies/jquery-plugin-production-check-20261015.md).
No global jQuery/core/UI removal or upgrade was included here. Browser production
checks were read-only; mutations and negative-access cases were exercised locally.
The previously documented unrelated static login PRG assertion remains outside
this change; this report does not claim every repository test passes.
