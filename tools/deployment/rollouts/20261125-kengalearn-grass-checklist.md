# KengaLearn premium grass checklist — 25 November 2026

Grassguide 0.017 adds account-scoped premium activation through canonical
Security API keys and Entitlement Service grants. Only Derek's named account
received the requested idempotent lifetime grant; administrator status does not
grant premium. Codes are private, shown once and individually revocable.
Authenticated checklist sync merges per-species changes with per-device sequence
numbers, avoiding lost-response replay. Different species merge; the last synced
change wins for the same species. Anonymous access and wrong scopes are denied.

Android 1.5 (code 6), local app commit `2e7fc34`, adds the public linked A–Z list,
offline queued premium checklist, open Description sections and original SVG
At a glance icons. Secure activation uses Android Keystore; browser activation
is tab-local. Offline entitlement checks last 30 days; lifetime grants do not
expire. Favourites remain separate. The app repository has no configured remote.

Framework: `548f94f6899ecc41b3e05e21ea863172aec2452e`.
Modules: `90600a2a0bfcc522b1108341750508618bb4a0e5`.
Manifest: `1799d9708ab35eed407220edb178a715d28c336d52e77fb1a7ed7621d0673a26`.
Operator: `/srv/kengalearn/grassguide-checklist-20261125/`.
Backup: `/srv/kengalearn/backups/shared-20261125-061338/`.

The release includes the already published shared password visibility and
personal-account provisioning fixes. Baseline source audit found no drift.
Clone rehearsal passed syntax, upgrade/replay and homepage/login/reset checks,
preserving all 194 protected tables. Live upgrade preserved 179 species,
648 free images and 202 commercially eligible images; worker restarted on the
same source. The named lifetime grant was verified through Entitlement Service.

Validation: app tests and Android build pass; packaged assets use production
endpoints, not fixtures. Isolated PDO tests exercise real grants/API keys,
premium denial, ownership, sequence gaps, merge, retry and transactional rollback.
Client tests cover two devices, offline changes, lost responses and changes made
during sync. Browser checks passed public navigation/search, open descriptions,
icon presence, activation, saved/synced checkmarks, filtering and revocation.
Mobile profile inspected; one later screenshot timed out, with DOM checks used
for icon presence. Test fixtures and preview servers were stopped. No real
permission email was sent and no live device credential was generated.

APK SHA-256: `0e91b5dc8cafa9551f37629c188e58b79b5130b5d1f635c5ce92bc8ba4ae0a44`.
Install over the existing app; activate via the signed-in Grass app premium
admin page. Physical-device installation remains for Derek to verify.

Final source audit passed for all 13,670 files in both web and worker. Live
anonymous premium/sync requests return 401; status writes return 405 and sync
preflight returns 204. Public catalogue remains HTTP 200 with 179 species.
