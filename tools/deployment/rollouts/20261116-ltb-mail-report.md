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

Status: isolated rehearsal in progress, production not yet switched.
