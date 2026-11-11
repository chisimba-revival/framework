# Learn-the-Birds readable addresses — 11 November 2026

Authorised after the owner accepted Chisimba.com. Includes the subsequently
requested shared copy-link icon for Pages, site posts and webinars. Only
Learn-the-Birds receives this deployment; other sites remain deferred.

- Framework: `03a8a901ab0730a059db96407379432559eaf0cd`
- Modules: `c535c7ae6b8ad1775e9244362347293a7ffab164`
- Common branch: `release/consolidation-20260930`
- Complete source manifest: `50361a716161ee1c92817e25cb70e6878a4d3248720d18fec66edfc9d0a7ddd3`
- Operator/evidence: `/srv/content-slugs-learnthebirds-20261111`
- Release: `/srv/learnthebirds.com/releases/common-dev-20261111-044638/ch`
- Backup: `/srv/learnthebirds.com/backups/shared-20261111-044638`

The previous complete source manifest matched before deployment. The isolated
clone rehearsed catalogue updates twice, dry-run/apply/repeat path allocation,
login/reset/homepage and all 141 generated content paths. All 113 protected
data-table fingerprints and existing navigation were unchanged both in rehearsal
and during deployment. Short URL's three new catalogue tags were separately
verified. No owner content, titles, authors, publication dates or statuses changed.
The clone had no external network, mail worker or writable production storage.

Short URL 1.015 was newly installed as a declared dependency. SimpleBlog updated
to 0.083 and Webinar to 0.030. Pages was not previously registered and was not
installed speculatively. Backfill created addresses for 29 posts and 112 webinars;
18 exceed 80 characters. Existing items can be shortened through Address name;
previous addresses are retained as aliases. No editorial shortening was guessed.

Existing host Apache WordPress redirects and media aliases are unchanged.
Fifty-two filesystem/legacy path names are reserved against accidental reuse.
Some generated titles that coincide with reserved WordPress paths receive the
standard `page-` prefix. A read-only backend Apache configuration mount in the
existing Compose service routes single-segment content paths while retaining
file/directory protection. The configuration survives container recreation/reboot.
The previous Compose file is in the backup. Other sites' routing was untouched.

The shared Copy link control reads the saved anchor rather than unsaved text,
shows translated success/failure feedback and uses the shared icon service and
skin. Webinar Ajax saves refresh the same anchor and reveal it after first save.
Checks covered rendering/escaping/accessibility markup, JavaScript syntax,
clipboard success, updated destinations and rejected clipboard access. The live
renderer and newly registered language labels passed a CLI check. No authenticated
browser clipboard interaction was performed during the production deployment.

Live HTTPS checks passed for post/webinar readable paths, original ID links,
representative WordPress post/event redirects, ignored query overrides, HEAD
(200), POST (405), unknown paths (404), and blog-listing readable links. The
13,543-file host/running-container source audit passed with read-only source.
The mail timer resumed and is active. No new fatal/SQL errors were found in the
persistent application logs during the checks.

Recovery retains the previous source plus verified database/configuration backups.
Do not restore an old database over later writes. Chisimba.com already has readable
addresses, but its copy-control update remains pending with the wider rollout.

## Follow-up: webinar action spacing

The owner reported touching Edit webinar / Watch video buttons on archived
webinars. The shared webinar detail renderer now groups these actions in the
skin's `chisimba-cluster`: horizontal alignment, an 8px token-based gap and wrapping.
Recording availability and edit permissions remain unchanged; booking content
remains outside the action group.

- Framework: `9e8ed952ead8910bf086264cec38760062a9c1de`
- Modules: `090d96252e94c048ca4efb8d6bda5d51cefe5365`
- Source manifest: `9fc5b963465a1163a2741541f9f5eabcf7e310581cfa0f2d6c5dc274303b44c2`
- Evidence: `/srv/webinar-actions-learnthebirds-20261111`
- Backup: `/srv/learnthebirds.com/backups/shared-20261111-051608`
- Release: `/srv/learnthebirds.com/releases/common-dev-20261111-051608/ch`

One source file changed. PHP syntax/archive contracts and the isolated clone
passed; 116 protected tables were unchanged. Browser verification on the owner's
archived seabirds webinar showed both buttons at the same vertical position,
with flex wrapping and an 8px gap. Public readiness checks passed.
