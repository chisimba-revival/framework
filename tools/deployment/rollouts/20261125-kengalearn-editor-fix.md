# KengaLearn editor dependency fix — 25 November 2026

Grassguide 0.020 explicitly loads grassguideproviders during the service lifecycle.
A fresh editor request previously reached the static photo-ID helper without
loading its class. PHP stopped rendering before the framework added the canvas,
leaving an unstyled partial page. The species and image data were intact.

The old fixture preloaded every module class and concealed this dependency.
A separate fresh-process regression now verifies service initialization makes
the photo helper available before rendering or saving media. This check and the
full PDO integration suite passed.

Feature commit: `f37fa96dc`; shared modules: `bed62901a7ec5ccc3be5080ab91dce441ee67edd`.
Framework: `42b16941fa5e1939b3eb5003430e9e9a124006cb`.
Manifest: `24c415e8ee63667052f7f781b519d1717af85a8703b0e786bd0b41f43b3d2b7d`.
Operator: `/srv/kengalearn/grassguide-editor-fix-20261125/`.

Baseline audit and isolated upgrade/replay passed, preserving 195 protected
tables and 179 species, 647 free images and 201 commercial images.

Live upgrade and HTTP checks passed. Backup: `/srv/kengalearn/backups/shared-20261125-064648/`. Worker restarted. Final web/worker audit passed all 13,673 files. Browser reload of the reported Eragrostis ciliaris URL verified the complete canvas, sidebar, image controls and footer; screenshot confirmed restored styling. No content mutations were performed.
