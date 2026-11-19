# Learn-the-Birds multiline video captions — 19 November 2026

Authorised target: Learn-the-Birds. Status: deployed and verified.
Framework: `2f46ffd899817716499ea01b38d01c0799a4c0ad`.
Modules: `f6ecc6a42904975f1885174fb1fc676ece359e59`.
Manifest: `5686e5d97434046b4428494614e3a14248df5ba95ac2340231bc316834b7b306`.
Evidence: `/srv/ltb-caption-20261119`.
Backup: `/srv/learnthebirds.com/backups/shared-20261119-042920`.

Contentblocks 1.040 uses a multiline textarea for shared video captions in
SimpleBlog and Pages. Display preserves newlines after HTML escaping. The
existing 2,000-character limit remains, and contextual video Help explains Enter.
No content rewrite or schema change is part of this feature.

Syntax, composition/video controls, Ajax draft preservation and editor escaping
checks passed. Isolated initial/repeat database rehearsal and installed runtime
checks confirmed the textarea and multiline rendering. All 116 protected tables
and existing site navigation were preserved. Homepage/login/reset HTTP checks
passed. Full host/container/mount audit passed 13,619 source files. No new runtime
error indicators; mail timer active. No live post was edited during verification.

The full common-source bundle also updates installed context 2.069,
contextcontent 2.108 and shop 1.018. Other sites retain their approved manifests;
MyHairPlanner's private-source inventory is unchanged.
