# Representative grass photographs — 27 November 2026

Grassguide 0.029 adds Default species photo to the species editor with a preview
and Automatic reset. Selection is saved with species revision checks, and must
belong to that species and be an active photograph. Feed ordering honours the
choice after applying rights/audience/state filters; excluded choices fall back.
The existing phone app uses this order after Check for content updates.
No schema or phone build changes. No production photo choice was made for the user.

Isolated PDO tests passed for selection, administrator access, stale saves,
cross-species rejection, reset and NC/premium/archive fallbacks. Browser checks
confirmed selection, preview visibility, saved confirmation and retained choice.

Framework: `a8498e3caf042bff456d2a46bb8a226c1a4b8699`.
Modules: `9a333140ad0b6f222abf43c2ea0fe7f1efe6d114`.
Manifest: `0fa5fa2e5d6856104cf6535d9f377de3e8a3645b88dcea7d4f28fea0caa2e35a`.
Operator: `/srv/kengalearn/grassguide-representative-20261127/`.

The first rehearsal stopped on older expected media counts. A read-only live
check confirmed 651 free and 208 commercial media across 179 species; the
operator expectations were updated and rehearsal passed, preserving 195 tables.

Live upgrade and HTTP checks passed; worker restarted. Backup:
`/srv/kengalearn/backups/shared-20261127-095118`.
Final web/worker source audit passed. Live browser confirmed the selector and
current photo choices on Aristida congesta; no production selection was changed.
