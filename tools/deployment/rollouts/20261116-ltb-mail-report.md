# Learn-the-Birds mail reporting — 16 November 2026

Authorised target: Learn-the-Birds, continuing the administration work.
Other sites retain their individually approved manifests pending wider rollout.
MyHairPlanner private source remains under its exact inventoried exception.

Framework: `4b59337afe7f61b1049db319af29200a233cd86f`.
Modules: `bf3c1885931de1ebcd2ed8a1f11cfe57f89b5c40`.
Manifest: `31bca7270530699e9f79752524dd1bf908e8524e4bab4896ae555fbb2cb8ed17`.
Evidence: `/srv/ltb-mail-report-20261116`.

Read-only administrator reporting adds seven daily sent totals, today's subject
counts and paginated recipient/subject/date/status filtering. Sent is transport
handoff, not inbox delivery. Legacy Communications wall-time storage is preserved.
No schema change, message retry or send is part of this feature.
Synthetic MariaDB boundary, filter, pagination and denied-access tests passed,
as did escaping/rendering and existing administration tests.

Status: deployed and verified.

Backup: `/srv/learnthebirds.com/backups/shared-20261116-175728`.
Rehearsal and deployment preserved all 116 protected tables and existing site
navigation. Repeat upgrade, PHP lint, language/registration and public HTTP
checks passed. Full host/container audit passed 13,593 source files and read-only
mount checks. No new error indicators; mail timer active. Anonymous log requests
require login. No browser automation was available for a fresh visual check;
rendering tests cover escaping, filter preservation and empty-day presentation.
No mail was manually queued, retried or sent by the implementation or checks.

## Text correction

Follow-up rollout in progress: decode translated HTML entities before escaping
report labels. Framework `9bc572a9f9dd1bd71cc1d0e31cc537772228ab9c`,
modules `d489371c2bdf99228468970b1a24fc320b5178c9`.
Manifest `e6393ecc2ab7d3dee318c87a14a5e697457de8d996c1b1f15c2b1fa02551b9ca`.
Evidence: `/srv/ltb-mail-text-20261116`. Regression rendering test passed.
