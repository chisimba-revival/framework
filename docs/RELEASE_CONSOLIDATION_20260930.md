# Five-site release consolidation — 30 September 2026

Status: **all five authorised sites deployed and online; verification limits recorded below**.

Derek authorised all recent feature work after validation, followed by deployment
to kengasolutions.com, kengalearn.com, learnthebirds.com,
heritagetreazures.com and kengapub.com. Chisimba.com is outside this rollout.
Derek subsequently approved publishing both release branches and today's brief,
one-site-at-a-time maintenance rollout. KengaLearn and KengaSolutions have been
deployed and reopened; subsequent sites are tracked below.
PHP/container upgrades remain separate. Preserve installation choices, site
configuration, payment settings, registrations, content, files and sessions.
Do not import contribution products or execute historical content import scripts.

## Preserved source boundary

Both primary repositories now use `release/consolidation-20260930`.
The existing approved working changes were checkpointed, not discarded:

- Modules: `5db2a6942`.
- Framework: `7e6f38c91`.

Fetching both origins did not supply all deployed changes. The release therefore
recovers missing source with explicit provenance, rather than copying the local
checkout over newer live modules.

Recovered registration commits: `203ea6214`, `dc7502c41`, `235aa9b7e`,
`2b59378a4`; framework abuse/registration styling: `a572b9873`, `e7f2a50f4`,
`f5f1bb349`. Conflicts retain bulk removal, individual review, audit and throttling.
Registration 1.024 enforces a 24-hour upper limit even if the installation still
stores the historical seven-day configuration. My Administration is 0.109.

Code-only archives from the deployed KengaSolutions tree are held below
`work/all-sites-20260930/baselines/` in the parent workspace:

- `chisimba-reconcile-modules-20260930.tar.gz`:
  `00ddb342f6c2c75d2e29d5e65c1e4af5baa26c031a8cb35f9f2f4ae717a9dfad`.
- `chisimba-reconcile-dependencies-20260930.tar.gz`:
  `df94cbdc502c60f759d76d1a229979b89ecf550a34b70f2c88afd17a22cbd8b3`.

The latter recovers UI metadata/brand icons and publishing composition helpers.
KengaLearn and LearnTheBirds lacked `ui/pagemetadata`; deploying the recovered
blog callers alone would have omitted a required dependency.

The older file `baselines/kengasolutions.tar.gz` is truncated and must not be
used. The initial `candidates/` overlays contain unresolved conflicts and are
stale relative to the source fixes. They are diagnostic artifacts, not releases.

## Resolved migration/access collision

Two historical SimpleBlog branches assigned version 0.070 to different changes.
SimpleBlog 0.075 adds a guarded forward union of provenance and membership fields.
It does not rewrite content or attribution. Its fresh-install schema contains both.

Combining the previous renderers could expose hidden composition text after only
`post_content` was restricted. `accesspreview::project()` now clears alternative
body representations for unauthorised readers. Listings, feeds and article
metadata use that projection; authorised structured summaries remain available.

Checks actually run so far:

- Registration expiry, foundation, web workflow, public-registration setting,
  My Administration, and shared submission-budget tests: pass.
- Publishing policy, balanced previews, alternate-body projection, metadata
  caller guard, terminology/Help and composition tests: pass.
- SimpleBlog 0.075 SQL exercised twice against old, access-only and import-only
  synthetic MariaDB 10.11.18 schemas: pass, preserving existing values and indexes.
  This used a network-isolated container with temporary in-memory database storage.
- Focused changed-file PHP syntax and both repository whitespace checks: pass.

## Reconciliation and initial rehearsal record

### Additional reconciliation and rehearsal evidence

The merged source also retains the deployed guest Yoco contribution flow alongside
the new signed-in contribution catalogue (Payment Service 1.034), Heritage audio
composition support (Content Blocks 1.029), native login destinations and catalogue
language repairs. Provider settings and contribution products are not changed.

Kanban 0.128 fixes an AJAX payload regression: capture FormData before disabling
controls, otherwise the board ID, CSRF token and enabled checkbox are omitted.
The executable JavaScript regression failed before the fix and passes afterwards.
Browser creation and revocation now succeed inline, retaining the expanded board.

The legacy catalogue patch reader ignores raw XML SQL elements. Explicit,
repeatable module hooks therefore execute only the reviewed SimpleBlog 0.075 and
Payment Service 1.034 forward migrations. SimpleBlog's existing permission
definitions remain in postinstall; upgrades do not grant publishing rights.
The literal hyphenated payment module hook name is covered by an engine-compatible
class alias and a regression test.

A separate local rehearsal uses the existing PHP 8.5.4 image and MariaDB 10.11.18,
with a clone of development data. The application and database have no outbound
network; only its proxy is exposed on loopback. Two complete catalogue-upgrade
passes succeeded for the thirteen selected modules, including the real hooks.
Question-bank synthetic integration checks passed: scope, duplicates, stale
revisions, private metadata, rollback, course deletion/reused codes, managers and
revocation. Native administrator login and Kanban AJAX create/revoke passed in
the browser. These are not yet full five-site production-runtime rehearsals.

The temporary runtime and fixture accounts were retained for browser checks,
then removed after validation. Its HTTP endpoint was loopback-only; production
TLS and the normal local runtime/database were not modified. Production
verification must not be reported as passed without evidence below.

Deployment manifests now explicitly exclude the web installer: absent production
installer entry points must not be reintroduced by source overlays.

The original release gates required per-site source/dependency comparisons,
including live-only fixes outside the initial manifest, reviewed merges,
published application source, isolated catalogue rehearsals and verified backups.
The following records distinguish completed deployment checks from browser-journey
coverage; not every authenticated role was exercised on every live site.

## Final release validation and production evidence

Published release source: framework `5083f3a79`, modules `feeb3ceba`, both on
`release/consolidation-20260930`. No merge to main or PHP/image upgrade is included.
Module Catalogue 3.138 preserves the deployed filter excluding orphan blocks for
uninstalled modules. SimpleBlog 0.076 removes its obsolete 2011 example-data seed:
a production-clone rehearsal caught catalogue updates adding two unwanted example
blogs. The final candidate passes two real catalogue-upgrade passes on isolated
clones of **all five sites**, with original-column protected-record hashes equal
before and after. Clone containers and their internal networks were removed.

Final v5 archive SHA-256:
`d637c51557d155fbb3ea2947ca8098ea41705d6d44d69185896ed6f43d244091`.
It contains 265 reviewed paths (192 writes, 73 removals). Operational helpers were
subsequently corrected to recognise KengaSolutions' configured canonical `www`
host; its initial host-guard rejection occurred before maintenance or live changes.

KengaLearn: active `/srv/kengalearn/releases/release-consolidation-20260930-131449/ch`;
previous `/srv/kengalearn/releases/release-legacy-endpoints-20260930-102622/ch`;
verified backup `/srv/kengalearn/backups/consolidation-20260930-131449`.
Public home/login, retained administrator session, existing map read and payment
administration rendering passed. No payment action was initiated. With explicit
permission for **one** production fixture, created map
`cc6dc11406f59c86a0553be1f5b2450e`, edited its note, observed autosave revision 2,
viewed the persisted note anonymously through its read-only public link, revoked
the link and confirmed unavailable. Deleted the fixture with exact-ID/title guards;
all four map tables contain zero records for that ID. No existing map was edited.

KengaSolutions: active
`/srv/kengasolutions.com/releases/release-consolidation-20260930-132832/ch`;
verified backup `/srv/kengasolutions.com/backups/consolidation-20260930-132832`.
Catalogue updates, source hashes and protected-record comparisons passed. Reopened
and verified canonical public home and login HTTP 200; homepage browser content
and site navigation rendered. Each backup's `release.json` records its exact
rollback path, image, archive hashes and reopening timestamp.

Heritage Treazures: active
`/srv/heritagetreazures.com/releases/release-consolidation-20260930-133407/ch`;
verified backup `/srv/heritagetreazures.com/backups/consolidation-20260930-133407`.
Catalogue upgrades, protected-data comparisons, source hashes and reopening home/
login checks passed. Browser homepage content, navigation and embedded video
surface rendered; no video was played and no enquiry was submitted.

Learn-the-Birds initially stopped **before live changes** because System Management was not
registered and its maintenance configuration is absent. The existing site remains
online. That first unsuccessful attempt created only an unused candidate/backup
directory. This blocker was subsequently resolved with explicit approval as
recorded in the continuation below; the maintenance gate was not bypassed.

KengaPub: active `/srv/kengapub.com/releases/release-consolidation-20260930-133656/ch`;
verified backup `/srv/kengapub.com/backups/consolidation-20260930-133656`.
Catalogue upgrades, protected-data comparisons, source hashes and home/login
checks passed; browser homepage and branding rendered correctly. Reopened at
`13:39:38 UTC` on 30 September 2026. New container PHP error match count: zero.
The later Learn-the-Birds continuation completed the fifth deployment.
The disposable local rehearsal containers/networks were stopped and removed;
their tmpfs fixture database was discarded. Verified production backups and
previous releases remain. Temporary on-host copies were removed after completion
as recorded below.
No real payment, email or paid-AI transaction is part of this verification.

Recorded reopening times (UTC): KengaLearn `13:20:07`, KengaSolutions `13:31:16`,
Heritage `13:36:15`, all 30 September 2026. Add two hours for South African time.
Their source manifest SHA-256 is
`fef1d43d8b9f730af265abbeb4d7f9ebb0c71061d6917fc6830ed4ccc1f74ddd`.
New container log checks reported zero matching PHP fatal/warning/deprecation or
uncaught-error entries on the completed sites at verification time. Production
authenticated browser checks were performed on KengaLearn, not all site/role
combinations; public page checks and isolated clone tests do not substitute for
those remaining site-specific authenticated journeys.

## Learn-the-Birds continuation

Derek approved installing the missing maintenance prerequisite and completing LTB.
Existing dependencies were already registered. A bounded v6 LTB candidate adds
the current System Management 0.6 source to the reviewed v5 manifest (277 paths).
Archive SHA-256: `e3a36cd2eb82065a95f74f257707440bf780006ee4f7dca03153b938fbe9df7d`;
manifest SHA-256: `ef6d576be743899968c60cdfd9f20b2a85266357654a757fda5fc0b536d85a70`.

An isolated LTB database clone first installed the exact old live maintenance
module (0.5), then ran the candidate catalogue upgrades twice, ending on 0.6.
All passes and protected-record comparisons succeeded. Maintenance contract,
Help and fake-email tests also passed with outbound network disabled.

Live prerequisite installation used the canonical module installer, existing
dependencies, paused web/mail/registration writers and a verified database backup:
`/srv/learnthebirds.com/backups/maintenance-bootstrap-20260930-134646`.
Protected records were unchanged, the notices table empty and maintenance initially
off. No notifications were sent and no provider configuration was changed.

The first main rollout attempt stopped before database upgrades because LTB's
Compose file requires an explicit environment file. Its old code pointer was
restored, the existing web container restarted and application maintenance kept
enabled. Verified in the browser: anonymous maintenance page and login HTTP 200.
Its backup/evidence remains at
`/srv/learnthebirds.com/backups/consolidation-20260930-134747`.
The operator now obtains the original environment-file path from the existing
container label, validates the exact known path without printing its contents,
and uses it for recreation/rollback. A guarded retry validates the failed receipt
and accepts only the maintenance state established by this same rollout.
The corrected operational helper is retained locally; it does not change app code.

### Completion and cleanup

Learn-the-Birds reopened at **13:52:50 UTC (15:52:50 SAST), 30 September 2026**.
Active release: `/srv/learnthebirds.com/releases/release-consolidation-20260930-135022/ch`.
Rollback code: `/srv/learnthebirds.com/releases/initial-20260914/ch`.
Verified backup: `/srv/learnthebirds.com/backups/consolidation-20260930-135022`.
Database archive SHA-256:
`a4049e36fff4c28a610bcb108358212d88b27fe0f9de8dd9fa4488bf045da0d3`.
Persistent-files archive SHA-256:
`72d3e488ad6c1a0028a07ef783a847760f05cb46f58cffc56fc39fb1c495e72b`.
The exact existing image `learnthebirds/php:20260920-webp` was preserved.
Installed affected-module versions and all manifest hashes passed; protected
records matched before/after. Public homepage redirects normally to upcoming
webinars; home/login checks passed. Browser checks verified the webinar cards,
branding, public blog listing and an existing article. New-container PHP fatal,
warning, deprecated and uncaught-error match count was zero at verification.
No production LTB account or content was created or edited for testing.

All five sites are now deployed and reopened. The other four sites were rechecked
and returned HTTP 200. No PHP upgrades, payment attempts, real test email or paid
AI calls were made. The role-specific and real-provider limitations above remain.

Cleanup used an explicit allowlist, verified online release receipts and retained
backup presence, and refused paths referenced by Docker mounts. It removed 9
temporary artifacts on KengaLearn and 32 on the shared host: disposable rehearsal
configurations (including copied installation keys), database dumps and staged
code copies. These disposable copies were discarded; the real data, verified
production backups, previous/current releases, manifests and test logs remain.
No rehearsal containers/networks or scheduled follow-up tasks remain running.

Application source release commits remain framework `5083f3a79` and modules
`feeb3ceba`. Infrastructure documentation is retained locally: its GitHub push was
blocked pending explicit permission to disclose infrastructure details. Do not
infer that approval from permission to deploy or install maintenance.
