# Grass Guide uploaded photo display — 27 November 2026

Grassguide 0.027 loads the catalogue service during photo service initialisation.
Fresh image requests previously called grassguideservice::unpack before the class
was loaded and returned HTTP 503. Production read-only diagnosis confirmed that
exception; explicitly loading the dependency read the existing 1,850,484-byte
image successfully. No photo re-upload, data migration or permission change is
needed. A new independent-process test avoids the preloaded-class blind spot in
the previous integration fixture.

Clear selected photos removes only file selection and previews, retaining captions
and credit fields. Upload dirty tracking compares the current form to its original
state (excluding renewed CSRF tokens), so clearing the only changes removes the
unload warning while remaining caption edits stay protected.

Isolated PDO suite, fresh-request regression and three JavaScript state tests
passed. Browser checks confirmed clearing two files removes previews and enables
clean Back navigation, and clearing files preserves an edited caption and its
unsaved warning. No production content was modified. No phone update required.

Framework: `cba1942bc1ceac39dd08e290a64248c6ec405e3d`.
Modules: `f9e91ec69ab82d04f2c752e6ae663d9675f6e4cb`.
Manifest: `4bb2f9829852928ef07f5172e2c6e82c4dd354196c0954c7af64cd7a31617ac4` (13679 files).
Operator: `/srv/kengalearn/grassguide-photo-display-20261127/`.

Live rollout passed upgrade and HTTP checks; worker restarted. Rehearsal
preserved 195 protected tables. Expected media counts were updated from
647/201 to 649/203 to include the two confirmed user uploads. Backup:
`/srv/kengalearn/backups/shared-20261127-084406`. Final web and worker
source audits matched the manifest. Live browser screenshot confirmed both
existing Aristida congesta photographs now render in their original cards.
