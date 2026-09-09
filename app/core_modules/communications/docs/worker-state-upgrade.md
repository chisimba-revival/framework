# Worker-state upgrade recovery — 9 September 2026

The module catalogue casts versions to floats. Communications used dotted versions 0.1.1 through 0.1.5, all of which compare as 0.1. Installing My Administration only checks that the Communications dependency is registered; it does not upgrade an already installed dependency. An older installation therefore lacked tbl_communications_worker_state and the dashboard query terminated through MDB2 before its catch block could handle the error.

Communications now uses catalogue-compatible decimal version 0.106. The real catalogue comparison is regression-tested against 0.1 and each dotted legacy version, including the earlier sender-name change at 0.1.5. The existing TABLE declaration and canonical catalogue installer create the new worker-state table during the update and register ownership. No outbox/attempt data migration is required. Do not change the installed version directly in SQL to conceal the missing update.

My Administration 0.107 checks table metadata before querying worker state. When absent it retains available mail counts, shows Communications update required, explains the catalogue action and disables queue execution. The server action checks the same condition before invoking the worker. Tests cover missing schema, no heartbeat, current heartbeat and stale heartbeat.

KengaLearn was repaired immediately using only the already-deployed Communications catalogue update (0.1.1 to 0.1.4), after a verified database backup at /srv/kengalearn/backups/communications-repair-20260909. Its missing table and ownership record were created. The authenticated My Administration page then loaded normally and showed the mail timer Running, queue 0 and failures 0. No manual queue run or test email was performed, and no production code switch or downtime was needed.

Source fixes at Communications 0.106 and My Administration 0.107 are prepared for a subsequent deployment. The separately prepared System Maintenance help/rename and sender-default changes remain subject to that deployment. Use the module catalogue update after deploying code; do not use Apply all updates for a bounded repair.
