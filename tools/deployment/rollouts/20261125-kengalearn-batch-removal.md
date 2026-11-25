# KengaLearn batch image removal — 25 November 2026

Grassguide 0.019 adds image checkboxes, Select all images, Clear selection and
Remove and do not reload to the species editor. Existing image editing and own
photo uploads remain independent. Retained excluded records block provider
re-import and can be restored individually. No production images were removed
by this release; catalogue updates carry subsequent removals to existing apps.

Admin/POST/CSRF guards apply. Each selected image revision and species ownership
is checked inside one transaction under the existing species lock. A stale or
invalid item rolls back the whole batch. Credits and provider metadata remain.

Feature modules commit: `39ff1b293`.
Shared modules: `b38f5340cea7ff710aa2f49383b5f8294c0c0d66`.
Framework: `eb66db75660e19e7514eb6a7b1a006902ab7417c`.
Manifest: `7d9ffdb0feac64cb207ca8effeccf2bb81f7b346a0a9e5d231acfc2987ce4264`.
Operator: `/srv/kengalearn/grassguide-batch-removal-20261125/`.
Backup: `/srv/kengalearn/backups/shared-20261125-063950/`.

Full isolated PDO suite passed, including batch permissions, malformed/duplicate
selections, stale-batch rollback, provenance preservation, public exclusion and
individual restoration. Browser fixture verified selecting and removing two
images in one action. Preview fixtures were cleaned up.

Baseline source audit passed. Clone upgrade/replay and live upgrade passed;
195 protected tables and 179 species, 647 free images, 201 commercial images
were preserved. Home/login/password-reset HTTP checks passed and the worker
was recreated successfully. No app rebuild required.

Final source audit passed for all 13,672 files in both web and worker.
