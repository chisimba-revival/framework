# Learn-the-Birds author-credit rollout — 11 November 2026

The owner requested manual attribution for imported posts, with Ajax matches to
users and optional webinar speakers. This implements a credit separate from
ownership, not account reassignment. Production post credits were not edited.

Initial feature deployment:

- Framework: `cf65936d0ffd3a58f8c105fdeeb02dc3cc9144e7`
- Modules: `002cd9ec0eec3c852ae613cc9e3cbbe4d2945408`
- Source manifest: `116418a58c91cd6ed48f76ed3444e1875d8cc42754430b53b7de1bde5d56223b`
- Evidence: `/srv/author-credit-learnthebirds-20261111`
- Backup: `/srv/learnthebirds.com/backups/shared-20261111-053634`
- Release: `/srv/learnthebirds.com/releases/common-dev-20261111-053634/ch`

The isolated clone rehearsed the additive migration and registration twice.
All 116 protected old-column table fingerprints and existing site navigation
remained unchanged; new author identity columns were separately checked as NULL.
The first rehearsal caught a variable-name collision in the operator's label
verification, before any production switch. Correcting that operator check and
rerunning the complete rehearsal passed. No application failure was suppressed.

Before deployment, source matched the previous complete manifest. Mail scheduling
was paused, writers stopped, database/configuration backups verified, source
switched read-only and registration applied through Module Catalogue. Public
home/login/reset checks passed. SimpleBlog is 0.084; UserDetails is 2.014.
No other sites were deployed.

Local functional/browser and anonymous HTTP checks are listed in README.md.
The live editor was checked read-only; no production article was saved as a test.
The visual check caught a missing text-input type, corrected in the follow-up
module commit so the author field uses the shared full-width styling.

Final styling follow-up:

- Framework: `cf65936d0ffd3a58f8c105fdeeb02dc3cc9144e7`
- Modules: `6886b069d75e036f34436033c9990cbcfd892074`
- Source manifest: `80bbd3a00244da2879239cc4236a8476c071f6c03db6ce8f5a5b914d2b15d126`
- Evidence: `/srv/author-credit-style-learnthebirds-20261111`
- Backup: `/srv/learnthebirds.com/backups/shared-20261111-054153`
- Release: `/srv/learnthebirds.com/releases/common-dev-20261111-054153/ch`

The one-attribute input-type correction received a fresh complete-source rehearsal,
backup and switch. All 116 protected tables, now including the new author identity
columns, remained unchanged. Browser inspection confirmed the field uses all
available width (1312px at the observed desktop viewport). No live post data was
changed during visual verification. Existing imported names remain available for
manual correction through the new field; no account creation is required.

Final verification passed for all 13,547 shared source files on the host and in
the running container, with read-only source mounts. The mail timer is active.
No new fatal/SQL errors appeared in persistent application logs for either switch.
