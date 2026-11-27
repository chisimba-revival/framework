# Grass Guide botanical typography and copyright — 27 November 2026

Grassguide 0.023 adds Italicise selection and previews to Similar species, Habitat
and Ecological references. Single asterisks store explicit italics as plain text.
Known catalogue names/genera and abbreviated names render automatically in prose,
image captions and labels. Authors and rank abbreviations remain upright. Raw HTML
is escaped. Inputs/select options and plain-text exports remain text; no existing
content is rewritten.

Photo batches support © All rights reserved, with explicit permission confirmation
for free/paid app use and a private evidence note. Existing Rights and publication
records bind the permission to the work. Uploads, NC replacement and permission
records are atomic; failed rights writes roll back and compensate files. Copyright
alone never grants publication. Public export preserves original_licence=copyright
and includes © photographer / used-with-permission credit, never private evidence.

Shared modules: `183994af997bb95f0071226a58116eeb1cd23abb`.
Framework: `54806b800cb292b1e2f6b8dc8349cf4882b50db9`.
Manifest: `74e7c98e526b559416cd2dff20a6c297b20dfc6b772f82da5d16bbb4b9a17a99`
(13,677 files). Operator: `/srv/kengalearn/grassguide-botanical-20261127/`.

App 1.9.2 (code 12), local source commit `39f4d99` (app has no remote configured).
APK SHA256: `aa979052d64e9e8d950a14bfd4efeb76b42bee65409f3e251a2a61a2aac4dce4`.
Installed with install -r on Derek's Samsung; version confirmed, no activation reset.
36 app tests and isolated PDO suite passed, including escaping, author/rank rules,
permission requirements, private evidence filtering, stale grants and upload rollback.
Browser fixture verified selection formatting, preview HTML, automatic names with
upright authors, and conditional copyright confirmation/evidence controls.
No production species or media content was edited during verification.

Live rollout passed upgrade, path, authentication and HTTP checks. Worker restarted.
Backup: `/srv/kengalearn/backups/shared-20261127-073334`. Post-deployment audit
matched all 13,677 manifest files in both web and worker, with no issues.
