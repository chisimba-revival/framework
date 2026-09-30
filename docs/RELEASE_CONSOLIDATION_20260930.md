# Five-site release consolidation — 30 September 2026

Status: **in progress; not approved for production deployment**.

Derek authorised all recent feature work after validation, followed by deployment
to kengasolutions.com, kengalearn.com, learnthebirds.com,
heritagetreazures.com and kengapub.com. Chisimba.com is outside this rollout.
No production code or database has been changed during this consolidation.
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

## Remaining release gates

Complete the per-site source/dependency comparison, including live-only fixes
outside the initial changed-file manifest. Preserve site branding and navigation.
Review automatic merges: an older deployed file is not proof that newer source
code should be deleted. Publish clean, scoped source commits and build new manifests.
Rehearse catalogue upgrades against disposable site database clones, run the full
merged-source behavioural/browser checks, and prepare verified backup/rollback
records before any maintenance or release switch. Follow the System Management
deployment runbook. Mark deployment complete only after all five active releases,
installed versions, protected-data checks and public/authenticated journeys pass.
