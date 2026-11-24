# KengaLearn species catalogue management — 24 November 2026

Grassguide 0.012 introduces a short Add species form requiring only genus and
epithet. Common name is optional. Existing workers discover new entries and fetch
available source data; the pinned GrassBase subset is not expanded by this change.
Catalogue cards expose Remove from catalogue with a named confirmation. Removal
archives the record, preserving media and history; Archived filtering exposes
Restore to catalogue, which restores the same ID as a draft. Seed import cannot
undo removal. Existing mobile clients receive changes through catalogue updates.

Source pins: framework `f6c252c024e74b8f83ac08c4f1fdc2002623847c`,
modules `af9d4256660a747fdf37b0274f6bd7aec85b9489`.
Full-source manifest: `268fb6c37b5e6d535a2006f066bc64cb230baf498ba8e7dbe5610d6ebb90ad21`.
Operator: `/srv/kengalearn/grassguide-membership-20261124/`.
Backup: `/srv/kengalearn/backups/shared-20261124-060058/`.

The pre-release source audit matched all 13,655 prior files on web and worker;
the only newer shared runtime source was this grassguide change. Full-source Git
bundle staging, PHP lint and database-clone upgrade/replay passed. All 194 protected
tables were unchanged. Production upgrade, authentication/home HTTP checks and
public export checks passed, preserving 179 species, 648 free-edition images and
202 commercial-preview images. The worker was stopped for the source switch and
recreated with the same verified read-only source. Existing NGINX and retention
configuration were preserved.

The real-PDO regression suite passed, including minimal creation, provider jobs,
public export exclusion, restoration with stable IDs and media, seed replay,
duplicate names, stale confirmations, audit rollback, permissions and CSRF.
Browser fixtures verified creation without a common name, remove/restore,
Archived filtering, contextual Help, Escape/focus return and a narrow layout
without horizontal overflow. The disposable fixture and database were cleaned up.
Screenshot: local `work/grassguide-membership-20261124/add-species.jpg`.
No production species were added or removed during verification.

Post-deployment source audit passed: all 13,656 files match on web and worker,
with no newer shared runtime changes against the deployed pins.
