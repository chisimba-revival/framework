# KengaLearn grass app updates — 21 November 2026

User authorised account-free app catalogue synchronization and an Android rebuild.
Installed grassguide 0.009 from common modules `25a852828f4eaa61bf65b5b49b4bcf9c997b863e`
and framework `6f92731f9e4c11c5dc51a776835d70f607a057cf`. Full manifest:
`cb691ea27e79ccdd6b5039c2c58b4727db9b54e2d8f16c8049eb4760df904603`.
All 13,652 files verified on host, web and recreated enrichment worker.

Operator evidence: `/srv/kengalearn/grassguide-sync-20261121/`.
Backup: `/srv/kengalearn/backups/shared-20261121-115923/`.
Clone rehearsal and live upgrade preserved all 194 protected tables and navigation;
repeat installation and public homepage/login/reset passed. No grass reimport.
Worker stopped before switch, then force-recreated with read-only source and no
ports. Future deployments must likewise recreate it; a Compose up of an unchanged
service alone can retain old bind mounts. Current worker compose/operator reside
in this operation directory, superseding the earlier deployment's worker command.

Enabled public app updates through the existing administrator publication form,
leaving noncommercial mode unchanged. The read-only `action=catalogue` route is the
only anonymous module action. Saved policy always applies; no query overrides.
NC images remain allowed. Editorial exports, settings, tasks, editing and previews
remain administrator-only; private evidence and notes are omitted from the feed.
Downloads already stored offline cannot be recalled by disabling publication.

Tests: isolated PDO suite including disabled feed, method checks, stable ETag,
policy-change invalidation, CORS, private field omission and restored guards.
Live app transport downloaded 179 species / 648 media, repeated with conditional
ETag and reopened persisted data without a network fetch. Live OPTIONS=204,
HEAD=200, POST=405; cross-origin header present. Anonymous admin routes returned
login forms. Browser confirmed saved publication toggle, app automatic/manual
update success and 390px layout. Source audit passed for web and worker.

Android 1.1 (versionCode 2) retains application ID and existing debug signing.
App startup opens cached/bundled content then checks the credential-free endpoint;
About & help offers manual updates. Validated atomic Preferences storage, size
limit, timeout, complete catalogue replacement and separate favourites protect
against partial downloads and preserve saved IDs. Thirteen Node tests passed;
Java 21 Gradle debug assembly passed. Device installation is not yet verified.
App source remains its existing local repository (no remote configured), commit
`5084f5f`; no new public repository was created. Server changes are on GitHub.
