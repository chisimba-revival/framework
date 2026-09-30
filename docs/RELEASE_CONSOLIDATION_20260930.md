# Five-site release consolidation — 30 September 2026

Status: **four sites deployed; Learn-the-Birds awaiting maintenance prerequisite approval**.

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

The original release gates required completing the per-site source/dependency comparison, including live-only fixes
outside the initial changed-file manifest. Preserve site branding and navigation.
Review automatic merges: an older deployed file is not proof that newer source
code should be deleted. Publish clean, scoped source commits and build new manifests.
Rehearse catalogue upgrades against disposable site database clones, run the full
merged-source behavioural/browser checks, and prepare verified backup/rollback
records before any maintenance or release switch. Follow the System Management
deployment runbook. Mark deployment complete only after all five active releases,
installed versions, protected-data checks and public/authenticated journeys pass.

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

Learn-the-Birds stopped **before live changes** because System Management is not
registered and its maintenance configuration is absent. The existing site remains
online. Permission to install the maintenance prerequisite has been requested;
do not silently install it, fake its configuration, or bypass the maintenance gate.
The unsuccessful attempt created only an unused candidate/backup directory.

KengaPub: active `/srv/kengapub.com/releases/release-consolidation-20260930-133656/ch`;
verified backup `/srv/kengapub.com/backups/consolidation-20260930-133656`.
Catalogue upgrades, protected-data comparisons, source hashes and home/login
checks passed; browser homepage and branding rendered correctly. Reopened at
`13:39:38 UTC` on 30 September 2026. New container PHP error match count: zero.
This is not yet a completed five-site rollout.
The disposable local rehearsal containers/networks were stopped and removed;
their tmpfs fixture database was discarded. Verified production backups and
previous releases remain. Protected on-host rehearsal artifacts remain pending
completion of the Learn-the-Birds gate and final cleanup.
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
