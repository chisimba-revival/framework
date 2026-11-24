# KengaLearn NC contributor directory — 24 November 2026

Grassguide 0.013 adds an administrator-only NC contributor directory and CSV.
Groups use exact credited names as contact leads, not verified owner identities.
Active NC image entries retain species, source links and recorded clearance status;
archived content and private evidence/notes are excluded from the export.
No contact messages were sent. The public feed contained 446 NC images credited
to 181 distinct names at verification.

Framework: `66ecc92c85e2607922caed360f9f0a108c9fb5f5`.
Modules: `f6a7c25f9d83e89ae1547639e154c9c6beb1f81d`.
Manifest: `cba8e26dbb1615d4db1eeea9de370b0de92e268668e9bf20dfd897351d5823e5`.
Operator: `/srv/kengalearn/grassguide-contributors-20261124/`.
Backup: `/srv/kengalearn/backups/shared-20261124-061348/`.

Full previous-source audit, Git bundle staging, PHP lint and database-clone
upgrade/replay passed; 194 protected tables were unchanged. Production upgrade
and HTTP checks passed. All 13,657 source files matched on web and recreated
worker, with no unexplained drift. The existing 179 species and 648 images were
preserved. Real-PDO tests covered credit grouping, active NC scope, permission
status, CSV formula defence and administrator boundaries. Browser checks covered
expanding source/rights links, downloading and parsing CSV, contextual Help,
Escape, keyboard focus and narrow layouts. Disposable fixtures were removed.
Screenshot: local `work/grassguide-contributors-20261124/contributors.jpg`.
