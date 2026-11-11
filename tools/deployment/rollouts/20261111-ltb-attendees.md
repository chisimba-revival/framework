# Learn-the-Birds attendee inspection — 11 November 2026

Authorised target: Learn-the-Birds only, for user testing with real registrations.
Common GitHub source: framework `d1d49b84997d0b0c4a701e850f7fda3e66030f8e`,
modules `9d84c4d358a551d5eb79f1220637a53b7cc3df1b`.
Complete 13,551-file manifest: `def6d929789e63cd95e8f2a831e2765c1b19b2aee37b37f7a8d6a30093923ae6`.

Rehearsal and deployment evidence: `/srv/ltb-attendees-release-20261111`.
This adds a private, read-only attendee list with conservative review hints;
no registration mutations or email sends are part of this release.
KengaLearn remains on its approved canvas bundle documented in
`20261111-kengalearn-canvas.md`; other sites remain on the preceding fleet bundle.
This explicitly staged rollout awaits wider authorisation before updating them.

Status: deployed and verified.

Backup: `/srv/learnthebirds.com/backups/shared-20261111-080408`.
Rehearsal and live updates preserved all 116 protected tables, including registrations.
Repeated upgrade, PHP lint, language registration and home/login/recovery checks passed.
Final host/container audit passed all 13,551 shared files and read-only mount checks;
no newer shared runtime changes remained for this target. No new error indicators
were found in application logs; the mail timer resumed. An anonymous attendee
request requires login. User will test the authorised listing with real data.
Source and this deployment record are pushed to the common GitHub branch.
