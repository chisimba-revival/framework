# Grass Guide caption lines and italics — 27 November 2026

Grassguide 0.025 replaces CSV batch-caption entry with one caption per line in
preview order. Commas, quotation marks and pipes are literal text. Windows and
Unix line endings work; blank interior lines and mismatched image counts fail
validation. Existing saved captions are unchanged. Batch captions and individual
caption editors now expose the existing Italicise selection control and escaped
preview; the existing app renderer already supports those markers.

Isolated PDO suite passed, including real photo batches, comma/quote preservation,
CRLF, trailing newline, blank-line/count rejection and explicit italic markers.
Browser verified selecting a caption name inserts asterisks and multiline preview
renders italics while preserving commas and line breaks. No production content
was edited. No app rebuild is required.

Framework: `ed1aff1a7f472824fbd3346dad0d54ec58439ad9`.
Modules: `cfdc0a54d48a5307afd2836cfd9133d8b059ec5e`.
Manifest: `92e57254a15e124c564336495e8b4029a3ba3d3a1f3be80e97942a3bae641525` (13,677 files).
Operator: `/srv/kengalearn/grassguide-captions-20261127/`.

Live rollout passed upgrade and HTTP checks; worker restarted. Rehearsal
preserved 195 protected tables. Backup:
`/srv/kengalearn/backups/shared-20261127-080927`. Post-deployment audit
matched all 13,677 files in web and worker with no issues.
