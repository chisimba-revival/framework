# KengaLearn image card spacing — 25 November 2026

The species editor now groups per-image actions in the shared chisimba-stack
footer and opts into chisimba-form-card--anchored. Buttons have explicit gaps,
stretch to the same width, and their group aligns at the bottom of each grid
card. No new CSS, content changes, schema changes or app rebuild.

Feature modules: `921d572dd`; shared modules: `1188361db1b17d7c27393fbbb06b7aef1d91c079`.
Framework: `d2be4a3aebcb166fd6ffaf90623a1246ca967490`.
Manifest: `a77a241a1f0b697b64122f1b5324f7c296e5b7f3cf7d34cd122c85f14462cf33`.
Operator: `/srv/kengalearn/grassguide-card-spacing-20261125/`.

Baseline source audit, PHP lint and clone upgrade/replay passed; 195 protected
tables and catalogue counts (179 species, 647 free images, 201 commercial)
were preserved. Verification focuses on live rendering for this layout-only change.

Live upgrade and HTTP checks passed. Backup: `/srv/kengalearn/backups/shared-20261125-070257/`. Web and worker source audit passed. Live browser screenshot of the reported species verified equal-width buttons, clear vertical gaps with wrapped labels, and aligned footer groups across all three cards. No records were changed.
