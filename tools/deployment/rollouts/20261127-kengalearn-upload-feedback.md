# Grass Guide upload feedback — 27 November 2026

Grassguide 0.026 labels selected photo previews as not uploaded, shows an
indeterminate progress indicator and Uploading and saving text beside the submit
button, and locks upload controls until the response. Errors appear beside the
button while retaining files and input. Browser-required-field failures also show
upload guidance. A confirmed save clears dirty state, resets the upload form and
returns to the species page; the non-JavaScript success route agrees. Pending
navigation keeps submit disabled. The unload warning still protects unsaved work.

The original report could not establish whether the batch was pending, rejected
or only selected; this change makes those states visible. No production photo
records were edited or re-uploaded during investigation.

Isolated PDO suite passed. JavaScript state tests cover a pending request, feedback,
control disabling, dirty protection, success/reset/navigation and rejected-save
recovery without losing input. Browser uploaded two synthetic images: a caption
count error was visible beside the button, retry retained the files, success
returned to the species page with Saved and the captions, and subsequent photo
navigation had no unload dialog. No phone update required.

Framework: `340e37c6f85f324b3dcb8a097ebf02c721f37767`.
Modules: `17b9d0c0e26046d6393036d0bdccb1d233b95f1e`.
Manifest: `953da71b1fc913e9f0e294d4077426a1bf259ae1480e1294de32a345e6e4ae78` (13678 files).
Operator: `/srv/kengalearn/grassguide-upload-feedback-20261127/`.

Live rollout passed upgrade and HTTP checks; worker restarted. Rehearsal
preserved 195 protected tables. Backup:
`/srv/kengalearn/backups/shared-20261127-082124`. Final web and worker
source audits matched the complete manifest with no issues.
