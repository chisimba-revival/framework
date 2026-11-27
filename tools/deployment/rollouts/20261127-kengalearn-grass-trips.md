# Grass App projects and trips — 27 November 2026

Grassguide 0.022 adds authenticated private sightings sync. New immutable sighting
IDs merge their species into the existing account checklist under one row lock;
lost replies can be retried without duplicates. Coordinates are optional and never
enter the public catalogue. Uses the existing checklist payload; no schema change.

Android 1.8 (code 9) adds Projects and trips, species-level Record a sighting,
local-first persistence, account sync and optional foreground GPS. Capacitor
Geolocation 8.2.5 provides Android's offline GPS fallback. This first version adds
and views sightings; edit/delete/rename/export are deferred. GPS is only requested
by an explicit button. Existing encrypted premium activation is preserved.

Shared modules: `4b70cf13ea9cc58add9e2b8016c03a167948903d`.
Framework: `8987daa573fbd45b16852e259484c1a9dc79efd8`.
Manifest: `98f4893c05b7daa6f96e678c2d48e3eb8908c2b9c3b022b957da61a788a71d1e`
(13,675 files). Operator: `/srv/kengalearn/grassguide-trips-20261127/`.
App source: `382e159` plus generated native wiring `1a7b804`, committed locally
(no app repository remote configured).
APK SHA256: `60d2f2b6830e9bb946dd3dde065f0226126ef746e1a83c8a2e14aa0f42fb59e0`.

33 app tests and the isolated PDO suite passed, including retries, offline writes,
concurrent additions, account boundaries, invalid coordinates and transactional
rollback. Browser synthetic preview saved blue-seed grass to a trip and confirmed
its main checklist tick; desktop spacing and narrow-layout overflow checked.
Physical offline GPS acquisition remains a field check. Clone upgrade/replay,
HTTP rehearsal and all 195 protected-table checks passed. Phone install verified
1.8/code 9 and the existing encrypted premium session file. No real test sightings
were added to the user's account.

Live deployment and web/worker source audits passed. Backup: `/srv/kengalearn/backups/shared-20261127-065509/`. Authenticated live sightings sync passed with an empty batch; anonymous POST returned 401. The temporary verification credential was revoked.
