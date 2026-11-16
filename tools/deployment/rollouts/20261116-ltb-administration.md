# Learn-the-Birds administration discovery — 16 November 2026

Authorised target: Learn-the-Birds. Other sites retain their individually approved
manifests until a wider rollout is authorised, including the KengaLearn assessment
release and the exact MyHairPlanner private overlay exception.

Source: framework `56b4aba587939267b73faa3f2785fe41d008468d`,
modules `9631770b859fe2d7655e9b132b29e76b187a005a`.
Manifest: `fb719e501d847a1761bdd9d9178cefb00fd2ed6de60a2f42fe84fe7a20ae127f`.

An administrator-only top-level site navigation entry leads to the existing
searchable administration workbench. Module registration adds mail health,
newsletter, subscriber, post and webinar tasks; My Administration links back.
Public navigation records remain intact. Opening these screens does not send mail.

Evidence and operator: `/srv/ltb-admin-release-20261116`.
Status: deployed and verified.

Backup: `/srv/learnthebirds.com/backups/shared-20261116-173452`.
The isolated rehearsal passed repeated catalogue updates, task registration and
language checks, PHP lint and public home/login/recovery HTTP checks. All 116
protected data tables and existing site navigation survived rehearsal and live
update unchanged. Live task registration checks and public HTTP checks passed.
The final host/container audit verified all 13,586 shared files and read-only
mounts against GitHub source. No new error indicators appeared; mail timer active.
Anonymous navigation hides the administration entry. Behavioural renderer tests
cover guest/member/admin/revoked roles and placement outside Account. Existing
workbench and My Administration contract tests pass. No browser automation was
available for a fresh visual check in this session; user can review the live UI.

