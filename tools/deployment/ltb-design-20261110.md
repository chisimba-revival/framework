# Learn-the-Birds design rollout — 10 November 2026

User approved the local content preview and deployment to Learn-the-Birds only.
The reviewed work was fast-forwarded and pushed to the common development branch
before bundle assembly:

- Framework: `7774886c54784804a33a52a18f897f1da4c96666`
- Modules: `9e9be7b3c0e1d1ca61a81a23037cc483b39ee0c4`
- Manifest SHA-256: `d515a3b718298f752e1c1c427308551c03427e27cf24672edbc7b46e0d7cfb79`

The pre-deployment audit matched all 13,530 previous source files. The isolated
server rehearsal applied useradmin 1.973 and SimpleBlog 0.080, repeated both
updates, checked native services and homepage/login/reset HTTP routes, and
preserved 114 protected data tables and existing site navigation.

Production was backed up and switched through the rehearsed operator. The
post-deployment audit matched all 13,535 source files, including container-visible
bytes and read-only source mounts. Existing content, configuration and navigation
were retained; the local preview database and account were not deployed.
Browser checks covered the actual webinar homepage, News and stories listing,
and 390px homepage layout with no horizontal page overflow. The mail timer resumed.

Server operation/evidence: `/srv/ltb-design-deploy-20261110`.
Backup/recovery record: `/srv/learnthebirds.com/backups/shared-20261110-122148`.

The other six sites remain on their recorded previous manifest pending user
approval. `fleet.json` records the LTB manifest and names the rollout target and
pending sites. A fleet audit should continue to report those sites as behind the
common development branch; do not overwrite their records to conceal that state.
