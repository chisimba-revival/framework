# KengaLearn species photo batches — 24 November 2026

Grassguide 0.016 adds administrator-only per-species photo batches with numbered
local previews, comma-separated captions (quoted commas supported), a batch
credit/licence and an optional radio choice to replace all NC images and stop
future NC imports for that species. NC images are archived, retaining their
records and rights history; other images and videos remain. The persistent block
survives species editing and additive uploads. A separate action resumes future
NC imports without restoring archived images.

File Manager owns validated JPEG/PNG/WebP storage. Media, NC archival and species
state commit atomically under the species row lock; failed batches compensate
new unlinked files. Provider imports check the current block under the same lock,
including results fetched before replacement. Revision checks reject stale
uploads/retries. Uploads retain original bytes and metadata. Limits are 10 files,
10 MiB each, 40 MiB total, 24 megapixels and 8192 pixels per side.

Owned photo delivery checks the current public app projection for anonymous
readers; administrators may inspect archived files. Generic File Manager routes
cannot expose module-owned assets. The app accepts the existing HTTPS image-URL
contract, so no Capacitor rebuild is required. Storage IDs and import settings
remain private. Public feed policy and NC filtering are unchanged.

Framework: `ce3c7661daf7d23d6120d52acdb041227c9ba387`.
Modules: `3be18b6e240a0787b665d6f3d4e8dad3c94e771e`.
Manifest: `b56d07bbd88248339a00270ebd035190ba58c08d283379624111a04f4ebb5bd1`.
Operator: `/srv/kengalearn/grassguide-photo-batches-20261124/`.

Validation: full isolated PDO suite passed using the common framework, with real
File Manager bytes, corrupt/unsupported image rejection, quoted captions,
transaction rollback and file cleanup, stale retry, retained bindings after edits,
worker NC block/resume and publication permissions. HTTP checks passed for anonymous
published bytes, admin archive access, HEAD lengths, CORS, method rejection and
private upload-page access. Browser tests passed two-photo upload, numbered local
previews, caption-count error recovery retaining files, replacement status,
contextual Help/Escape and a narrow layout without horizontal overflow. Screenshot
capture timed out; visible DOM state verified the saved result. Disposable preview
services and storage were cleaned up. No real photographs were uploaded and no
permission emails were queued or sent during testing.

Production preflight confirmed File Manager storage is writable by the web user
and PHP limits (64 MiB upload/post, 20 files) accommodate the batch limits. Baseline
audits found no source drift. Clone rehearsal passed lint, upgrade/replay and HTTP
checks while preserving all 194 protected tables.

Production upgrade and HTTP checks passed with the existing 179 species and 648
images preserved. Backup: `/srv/kengalearn/backups/shared-20261124-071035/`.
Web and worker run the same verified release. The live missing-photo endpoint
returns 404 without an authentication redirect. Post-deployment source audit
passed for all 13,664 files on both containers.
