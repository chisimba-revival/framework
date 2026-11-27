# KengaLearn Grass App premium release — 27 November 2026

Grassguide 0.021 adds authenticated commercial-compatible premium feeds and photo downloads, premium-only YouTube links, and Everyone / Premium only photo audience controls. Android 1.7 (versionCode 8) adds Kenga Solutions branding, creator credit and visible edition status, filtering NC photos in premium mode while retaining the existing account-synced checklist. No payment checkout or project sightings feature is included.

Feature modules: `534ac7aba`; shared modules: `e1657b39e59b5ec7ba2f83bcef47eeb4e377e394`.
Framework: `975a234aa297a0c7a57370681862ae1440ec231e`.
Manifest: `e6d29656482be03e474cc34d2b8ffeac402fbc0700907af3700d78a990ac3ec8` (13,674 files).
Operator: `/srv/kengalearn/grassguide-premium-20261127/`.
Backup: `/srv/kengalearn/backups/shared-20261127-044258/`.

Backend integration tests, 29 app tests, PHP lint, clone upgrade/replay and HTTP probes passed. All 195 protected tables were preserved. Web and worker post-release source audits passed. Live anonymous premium feed returns 403; a temporary scoped credential verified 179 species and 201 premium media with no NC licence codes, then was revoked. The free catalogue retains 647 media.

Android source commit: `d6c39a0` in the local `codex/lowveld-field-guide` app repository; no remote is configured there. APK SHA256: `a5089ca5bf2f3b26ccda2d82a5bee160cecd8f6edc25cde7d4f466a107552cf7`. Installed with `adb install -r` on Derek's connected Samsung; package metadata confirms 1.7/code 8. Existing app data retained.

Derek's canonical account has lifetime premium. At verification it had no active Grass App device codes; entering a device code on the phone remains a user step. Do not embed account credentials in distributed builds. Browser preview returned older UI despite a fresh origin, so visual verification of the new branding was not established through that browser session.
