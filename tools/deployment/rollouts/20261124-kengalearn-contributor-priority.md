# KengaLearn contributor priority and permission wording — 24 November 2026

Grassguide 0.015 orders NC contributors by active image-entry count descending,
with alphabetical ties and retained zero-image contacts last. The standard request
adds the approved CC BY/CC0 sharing paragraph, retaining “can create barriers”,
and uses the user's simpler “Please grant me permission” closing and alternative
credit wording invitation. Existing drafts and queued snapshots are preserved.
“Use updated standard request” replaces only the draft body; Save contact and
preview is still required. Personalised openings and signatures remain intact.

Framework: `7edbae648ebf86fb6bea915f8e407291b270705d`.
Modules: `fefbbf2ad5441a84a261bf364c453aad7b693d8a`.
Manifest: `507e64c8c54c26db5aed7fcd4650ef01d0b25a50cdf46efac23cbc418b62e024`.
Operator: `/srv/kengalearn/grassguide-contributor-priority-20261124/`.
Backup: `/srv/kengalearn/backups/shared-20261124-064809/`.

The isolated PDO suite passed, including permission requests, transactional outbox,
privacy and snapshot preservation. Browser verification confirmed that replacing
old draft wording restores the approved text and saving retains the personalised
opening and signature in the preview. Screenshot capture timed out; DOM state
confirmed the saved result. Disposable preview containers and network were removed.
No production contact messages were queued or sent; no test transport was invoked.

Baseline audits found no deployed drift. Clone rehearsal passed lint, module
upgrade/replay and HTTP checks, preserving all 194 protected tables. Production
upgrade and HTTP checks passed with 179 species and 648 images preserved. Web and
grass worker were recreated on the same complete source release.

Post-deployment source audit passed for all 13,660 files on web and worker.
