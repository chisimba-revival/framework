# KengaLearn grass catalogue — 21 November 2026

Authorised administrator-only deployment. Grassguide 0.008 installed and utilities
updated to 2.027 through Module Catalogue. NC images remain allowed; there is no
public feed, subscription or change to the installed phone APK.

Source: framework `60ddfe08e16c8875950bc43819c7c55309d6bc2b`, modules
`424779aeea2219fc6bca222006f0157e1de2335d`, common development branch.
Manifest `c98c001d24b02fbb69509cb0bfa65f546f87c62016b4433b44027ef583b43b11`
verified all 13,652 files on host and web container, without private overlays.

Evidence: `/srv/kengalearn/grassguide-release-20261121/`.
Verified full database/configuration backup:
`/srv/kengalearn/backups/shared-20261121-114902/`.
Previous source retained; configuration, uploads and sessions remain separate.

Clone rehearsal and live migration preserved 189 protected tables and existing
site navigation. Only grass content migrated: 179 species, 648 media, 1,206 audit
records and 895 jobs, retaining IDs. Snapshot checksum and empty destination
checks precede import; counts checked afterwards. Rights table starts empty.

Passed strict PHP lint, repeat installation, anonymous service guard, catalogue
exports (648 free images, 202 commercial-eligible images), homepage/login/reset
smokes, and final full-source audit. Live anonymous requests for catalogue,
setup, rights, task editor and both exports returned login forms. Existing admin
browser session showed the rights audit. Module fixture tests cover ordinary and
revoked admins, POST/CSRF, stale edits/permissions, and transactional rollback.

## Worker

`kengalearn-grassguide-worker` shares the site's source/configuration/database,
with no ports and read-only source. Operator `worker.compose.json`, project
`kengalearn-grassguide`, pins the web image; restart policy unless-stopped,
rotating logs 10 MB × 3. Each minute queues due work and runs bounded batches for
GrassBase, Kew, GBIF, Wikipedia and iNaturalist. First full cycle: zero queued,
five providers idle, zero restarts. Desktop worker stopped; local data retained.

Worker acquires a shared `/srv/kengalearn/shared-release.lock` during each batch.
Future deployments must stop it before source switch and recreate it afterwards:
Docker mounts otherwise retain the previous resolved release. Update the next
operator's manifest expectation before using `start-worker.py kengalearn`; never
bypass source verification. The existing deployment operator takes an exclusive
lock to prevent overlapping writes.

Phone catalogue synchronisation and paid access remain future work. Export omits
private task notes and permission evidence. Replacement dates are tracking fields,
not scheduled email notifications.
