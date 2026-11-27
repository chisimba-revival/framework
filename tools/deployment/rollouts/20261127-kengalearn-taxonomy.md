# KengaLearn grass taxonomy — 27 November 2026

Deployed module 0.030 from common modules `236663c17` and framework
`25fdd1ab4` using the complete-source manifest workflow. Feature commit:
`bda9fdc40` on `codex/grassguide-management`; both module branches pushed.

- Release operator: `/srv/kengalearn/grassguide-taxonomy-20261127`.
- Verified backup: `/srv/kengalearn/backups/shared-20261127-131833`.
- Clone rehearsal and live transaction: 179 retained records, 173 visible entries,
  168 accepted POWO names, five unresolved names retained with warnings, six redirects.
- All 651 free media and 208 commercial media retained. Media IDs, payloads,
  permission records, default photos, editorial fields, checklists and immutable
  sighting records verified. Repeat reconciliation makes no changes.
- All 195 business tables protected during source upgrade; taxonomy changes
  restricted to species/media/audit tables, with explicit preservation assertions.
- Anonymous homepage/login/reset probes passed. Web and worker manifest audit
  passed (13,683 files); worker running. Other sites untouched.
- Real-PDO tests cover fresh import, rollback, repetition and historical IDs;
  browser checks cover warning/Help, app alias navigation and former-name search.
- Android 1.9.5 (15), 40 tests passing, debug APK built with the live catalogue.
  Phone installation pending USB connection. App commit `4a61b10`, no remote configured.

Unresolved names: Eragrostis heterostachya, Microchloa caffra, Urochloa meyeriana,
Urochloa oligostachya, Urochloa stapfiana. No identity was guessed from a similar
name or conflicting author. Original import CSV retained as provenance.
