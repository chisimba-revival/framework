# Grass Guide save controls — 27 November 2026

Grassguide 0.024 makes Save changes available above the species form, after each
editing section, in Editorial review and before the media section. Every button
saves the whole species form. Nearby statuses show unsaved changes, saving and
errors; all submit buttons are disabled during a save. Existing revision, CSRF,
validation, draft preservation and unload protection remain in place.

Isolated PDO integration suite passed. Browser verification saved Similar species
from Identification, confirmed the retained text and success notice, then opened
Upload photos without an unload warning. Save buttons fit desktop and narrow
viewports. The existing isolated preview was reused because its port was occupied;
the new suite completed successfully before its preview startup reported that port
conflict. No production content was changed during tests. No app update needed.

Framework: `74bab3747480aa0b7614a0bf518e08c59f33f9ad`.
Modules: `895337c3cbe64f3b5f4cef9a69b46ca2bb4932ef`.
Manifest: `76eb818389e3057a8a5e796a677714a29073c553323602eafc5081fc87d82b91` (13,677 files).
Operator: `/srv/kengalearn/grassguide-save-20261127/`.

Live rollout passed upgrade, authentication and HTTP checks; worker restarted.
Rehearsal preserved 195 protected tables. Backup:
`/srv/kengalearn/backups/shared-20261127-075909`. Post-deployment audit
matched all 13,677 files in both web and worker with no issues.
