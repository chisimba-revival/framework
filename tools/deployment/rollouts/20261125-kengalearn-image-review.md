# KengaLearn image review — 25 November 2026

Grassguide 0.018 adds an explicit incorrect-image exclusion with a required
private reason. Records remain for review/restoration and act as per-species
photo tombstones. Imports compare canonical iNaturalist photo IDs, not image
size URLs. Media saves and worker merges share the species lock. Public and
commercial projections omit exclusions; existing app versions receive removals
on catalogue refresh. Disconnected copies persist until they reconnect.

The paced worker matches exact photo membership against up to 100 most-liked
observations, fetched in four bounded pages of 25. Casual, Needs ID and Research
Grade are observation metadata, not confidence guarantees. Unmatched/ambiguous
photos remain Grade unavailable. Grade refresh preserves editorial captions,
provenance and rights fingerprints. No observation locations/comments/user
profiles are stored. Canonical API docs: https://api.inaturalist.org/v1/docs/.

Derek confirmed the Douglas Goldman photo 412627651 was incorrect for
Eragrostis ciliaris. Its record was excluded with the requested reason and
retained. The normal queue added 157 source checks, with this species prioritised.
Live worker processing and the subsequent public feed verified five remaining
photos, three matched Research Grade and two unavailable. The remaining grade
lookups continue in the existing worker; no image was excluded merely for its grade.

Framework: `fa5cf6865e004954b82c6d6dd8a3d7e3052b1a69`.
Modules: `508e85813be04fff188027f02d6390f7a3fc0182`.
Manifest: `daaaf91005d7db34001fd3fb4309175dc52f37c5e783f2cd30bf89b7fdb949b0`.
Operator: `/srv/kengalearn/grassguide-image-review-20261125/`.
Backup: `/srv/kengalearn/backups/shared-20261125-062919/`.

Baseline source verified. Rehearsal passed lint, upgrade/replay and HTTP checks,
preserving 195 protected tables. Live upgrade passed before the explicitly
requested content change. Final source audit passed all 13,671 files in web and
worker. After exclusion the bundled snapshot has 179 species, 647 images, 446 NC
and 201 commercially eligible images. Rights tracking assignments were preserved.

Full isolated PDO suite, app tests and Android build pass. Tests cover exact
photo grades, exclusions, permission/stale-save checks, re-import blocking across
URL/import-ID changes, restoration, private reasons and rights-evidence stability.
Browser tests verified exclusion save, Help/Escape, live grade/observation credit
and unavailable-grade display. Screenshot capture timed out; DOM checks verified
the displayed states. Disposable fixtures and preview servers were stopped.

Android 1.6 (code 7), app commits `9e55780` and `eada6ba`, bundles the corrected
catalogue. APK SHA-256: `17b3b46598c1b067d6580989b7bd40158e60968e0d705875af0a892fd85592c3`.
The standalone app has no configured Git remote; its commits are local. Backend
and deployment records are published on the common release branch. Physical
Android installation remains for Derek to verify.
