# KengaLearn Active Knowledge Map update — 19 November 2026

Authorised target: kengalearn.com. Deployed and verified.
Framework `fdcec58a50983161713b5dfc7abd26ac7ae34c6a`; modules `f6ecc6a42904975f1885174fb1fc676ece359e59`.
Common source manifest `6213c7e5c45de500fd3808c97ca32fdd0345eef9c14104f0e777a01542f2ddc7`.
Operator evidence: `/srv/kengalearn/akm-release-20261119`.
Backup: `/srv/kengalearn/backups/shared-20261119-134229`.
Release: `/srv/kengalearn/releases/common-dev-20261119-134229/ch`.

Knowledge Map 1.03 adds semantic course outlines, clockwise chapter ordering,
bulk page assignment, direct Quiz Note input and compact toolbar palettes.
MCQ 4.195 supports linked question banks and fixed/per-attempt random draws.
The shared skin includes the soft floating-panel shadow. Installed context,
toolbar, contentblocks, contextcontent and myadmin also receive common updates.

Previous host/container source matched its approved manifest. Candidate PHP lint,
map editor/contract and shared skin tests passed. Isolated catalogue upgrades ran
twice; clone homepage/login/reset checks passed. All 186 existing protected tables
and site navigation were preserved in rehearsal and live update. Fresh database
and configuration backups verified before switching. Uploaded content mounts were
not replaced. Final host/container audit passed all 13,619 shared source files and
read-only source mount checks. Live public routes passed; authenticated Grasses
map displayed the new semantic palette and computed shadow. No live map edits,
course generation or paid AI test requests were made. Existing quiz worker uses
the updated shared entry point. Other sites were not deployed by this operation.
