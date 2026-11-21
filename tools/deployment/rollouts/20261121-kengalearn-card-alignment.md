# KengaLearn card alignment — 21 November 2026

Audit review buttons now sit at the bottom of their cards, aligned across each
row despite different title and image heights. The shared skin supplies an
opt-in anchored-card modifier and footer; the grassguide template adopts it.
No data, permissions, publication policy or mobile-app changes.

Common source: framework `addc8d9e3e485b44ca1cf84cf3c3045e9bb6393f`,
modules `766dce477309b2c784a286abf5990d78a732e1ef`.
Manifest: `dedb9f631b140ed8495a7a0d931c5f0fda3ac3893840fb701b77408799e65a56`.
Operator: `/srv/kengalearn/grassguide-card-alignment-20261121/`.
Backup: `/srv/kengalearn/backups/shared-20261121-131553/`.

PHP syntax and whitespace checks passed. Full-source baseline verified before
staging; clone rehearsal preserved 194 protected tables. Upgrade and HTTP smoke
checks passed. Worker was stopped for the switch and recreated against the new
read-only source. Browser measurements confirmed identical button bottom edges
in the reported three-card row after images loaded. Keyboard focus remains
visible; narrow-screen cards retain contained buttons. Screenshot recorded in
local work/grassguide-card-alignment-20261121/aligned-cards.jpg.

Post-deployment full-source audit passed for all 13,652 files on web and worker.
