# Global jQuery default-off rollout — 17 October 2026

Runtime release: framework `6f3acbca2`, modules `3c5a448c1`.
Chisimba.com and KengaLearn passed their earlier rollouts and Derek's checks.
Learn-the-Birds, KengaSolutions, HeritageTreazures and KengaPub then received
that same committed runtime release, restricted to their deployed module sets.
Uncommitted local module moves were excluded.

Each site had a Git-history drift audit, staged PHP checks, an isolated database
rehearsal, repeat catalogue update, public home/login/reset probes, verified
backups, a stopped-writer switch and deployed-file/log checks. Existing site
navigation was checked separately. Mail timers were resumed and verified where
present. No test emails, orders or payments were triggered.

| Site | Changed/retired files | Protected data tables |
| --- | ---: | ---: |
| Chisimba.com | 523 | 107 |
| KengaLearn | 523 | 170 |
| Learn-the-Birds | 523 | 108 |
| KengaSolutions | 417 | 216 |
| HeritageTreazures | 417 | 216 |
| KengaPub | 417 | 220 |

Table counts reflect each rehearsal's scope, not the site's total tables.
Catalogue/configuration bookkeeping is excluded from business-data fingerprints.
KengaLearn's first rehearsal observed normal expiry of a login-presence row;
subsequent checks explicitly excluded that temporary presence table. The later
Discussion migration also excluded its expected catalogue patch-history writes.
All protected table fingerprints matched before/after the successful updates.

Chrome checks found no global jQuery script elements or console errors on the
inspected pages: signed-in KengaLearn home, Kanban expansion and an existing map;
Learn-the-Birds webinar list, management and dashboard; KengaSolutions home and
Photos menu; HeritageTreazures home and sign-in; KengaPub storefront and cart view.
These are smoke checks, not exhaustive acceptance of every installed workflow.
CMS/ePortfolio compatibility remains explicitly scoped; dormant modules were not
activated. Backups remain on each host; disposable rehearsal databases and
credentials are removed after receipt collection.

## Reviewed Discussion upgrade and remaining catalogue follow-up

KengaSolutions, HeritageTreazures and KengaPub had Discussion 3.035. Their pending
3.036 XML patch widens `tbl_discussion_post_ratings.userid` and `modifierid` to
32 characters. The release operator initially refused the unreviewed schema
change. Review pinned the exact XML SHA-256:
`722e3ba4775282aa6474b07748c4fa59fc013137c4a4ff7f356c6a1bd29ffdd9`.

The general `patch::applyUpdates` runner then failed in the isolated rehearsal.
The bounded release migration instead used the shared `modulesadmin::alterTable`
validation and execution paths, wrote a scalar JSON patch receipt through dbTable,
updated registration through Module Catalogue and verified both column widths.
The full rehearsal and repeat run passed before production; business rows were
unchanged. No general schema-review safeguard was disabled.

**Follow-up resolved in source on 2026-10-19:** the general Module Catalogue
patch runner repair and regression tests are recorded in
[module-patch-runner-20261019.md](module-patch-runner-20261019.md), framework
commit `a3fa6b839`, Module Catalogue 3.142. The original finding follows for history.

Source tracing found patch receipt inserts containing arrays/SimpleXML values,
where the native database boundary accepts scalar parameters. Its caught
exceptions can also redirect/exit without useful CLI diagnostics. Verify the
failure directly, normalise receipt fields, preserve checked schema execution,
and test real pending XML updates, repeat runs and error propagation in disposable
PDO fixtures. This is a separate repair; the release-specific migration does not
claim to fix the general runner.
