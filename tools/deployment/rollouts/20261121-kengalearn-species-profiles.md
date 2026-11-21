# KengaLearn species profiles — 21 November 2026

Grassguide 0.011 adds cached, editable GrassBase key features and safely matched
GBIF taxon identifiers to the existing licence-filtered public catalogue. Manual
edits take precedence. Management remains admin-only; NC policy is unchanged.
The existing worker refreshed 165 species with four sourced highlights each;
172 of the 179 species have verified map identifiers. No invented highlights
are supplied for the 14 species without matched GrassBase data.

Common source: framework `6fb69839f63fed4dbd7702d4b0c4731b457f9714`,
modules `2204956330be09269bdf872408aa0c80861f2386`.
Manifest: `37a23d2745f9f13b8ef930cba76fe53396b01a8fe61615d87994f27e1164d74b`.
Operator: `/srv/kengalearn/grassguide-profiles-20261121/`.
Backup: `/srv/kengalearn/backups/shared-20261121-160431/`.

Full Git bundle staging, PHP lint, normal catalogue upgrade and clone rehearsal
passed; 194 protected tables were unchanged. The isolated PDO suite passed,
including manual overrides, exact-name map guards, rights filtering and existing
permission/CSRF tests. Both source mounts were recreated; the worker is running.
Post-deployment audit passed for all 13,655 source files on web and worker.

Companion Android app 1.4 (version code 5) puts key features first, groups detailed
characters, removes the repeated botanical-description section and moves source
notes below the identification content. GBIF maps request only CC BY and CC0
occurrences, handle empty tiles, and retain attribution. Maps require internet.
The iNaturalist taxon-photo API has attribution but no descriptive caption field;
manual captions remain visible and generic import placeholders are suppressed.

Two Python tests and 18 app tests passed. Browser checks covered real occurrence
tiles, map zoom, gallery, detail links, help, and a narrow viewport without
horizontal overflow. Android assembleDebug passed; physical-device installation
is left to the user. Screenshots and source audit are retained locally under
`work/grassguide-profiles-20261121/`. The installable APK is
`grasses-of-the-sa-savanna-app/releases/lowveld-kruger-v1.4-debug.apk`.
