# KengaLearn private grass audit — 21 November 2026

Corrected the audit placement to the private website administration. Grassguide
0.010 adds the shared right-hand administration menu, wider form cards, photo
previews, replacement task totals and priority/planned/resolved filters. No schema
or publication-policy changes. NC media remain allowed; app updates remain enabled.

Published common source: framework `8efa77a91076d7ff58818410c72de176e46d327e`,
modules `d2ef68eba05e703044de75161e4a59c3bef780f9`.
Full manifest `30a5f9e96c325f300326d52511c456fe7ffd68cce69c791caf43bd097677cbaa`.
All 13,652 files verified on the host, web container and recreated worker.
Operator evidence: `/srv/kengalearn/grassguide-admin-20261121/`.
Backup: `/srv/kengalearn/backups/shared-20261121-124315/`.

Clone rehearsal and live upgrade preserved all 194 protected business tables and
navigation. Repeat upgrade, PDO smoke checks and homepage/login/reset passed.
Worker stopped for the switch and force-recreated against the new source mount.
The isolated grassguide test suite passed, including administrator restrictions,
CSRF, publication policy and the new audit views/filters. Browser verification
confirmed the sidebar, wider setup page, private audit totals, photo previews,
planned filter with zero matching tasks, and help open/Escape. Anonymous admin
routes show no audit content; public catalogue returns 200 without task fields.

Android 1.3 (versionCode 4) removes the mistakenly added public audit tab and
baseline. Existing application ID, signing, favourites and catalogue updates are
retained. Thirteen Node tests and Java 21 Gradle debug assembly passed. APK:
`grasses-of-the-sa-savanna-app/releases/lowveld-kruger-v1.3-debug.apk`.
App changes are committed in the existing local repository through `7a35354`;
there is no configured remote. Installation on a device is left to the user.
