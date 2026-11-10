# GitHub source audit — 10 November 2026

Follow-up: the [common development rollout](../common-development-20261110/README.md)
reconciles these historical differences. This document records the earlier audit,
not the current deployment status.

The maintained branch is `release/consolidation-20260930` in both repositories.
Completed local maintenance and reviewed shared fixes recovered from production
are now on that branch. Private HairPlanner business code is excluded.

## Evidence and limits

`snapshots.json` identifies immutable `audit/2026-11-10/<site>` tags in both
repositories. These record the audited source bytes of seven live sites, not
seven maintained development branches. They are historical recovery evidence,
not approved release candidates and not evidence that all sites run the latest
common release. Future deployment must use the common branch and a reviewed
common manifest; do not build another site-specific branch from these tags.

The audit covered classes, core_modules, skins, lib, cron, iconthemes, installer,
packages and the standard root PHP entry points. Framework paths have `app/`
prefixes; module paths omit the runtime `packages/` prefix. 110,864 file entries
were matched to their Git blobs. File modes in snapshots are normalised; these
are source records, not complete container/filesystem backups. Environment,
server routing, configuration, uploads, database content and generated caches
are outside these source snapshots. Obsolete `.orig` files and a stray non-code
import artefact are excluded. Existing backups remain the recovery source for
runtime data and infrastructure.

MyHairPlanner additionally excludes the `hairplanner` and `imageedit-service`
modules and its private REST controller/base/trait overlay. Those remain private.
No production upgrade was performed on MyHairPlanner during this audit.

## KengaSolutions retirement

The owner confirmed its HairPlanner copy was obsolete. The two private modules
and private REST trait were removed from a new release; public REST controller
files replace the private adapters. Normal module-catalogue reconciliation was
used, without dropping private tables or media. A database backup and previous
release remain on the server. Homepage health passed; the former public API
route returns HTTP 410. MyHairPlanner workers and runtime were left untouched.

## Shared work recovered

The common branch includes navigation/category/label maintenance, country
ordering, course membership access, picker thumbnails and publication metadata,
page display/reusable links, shared UI/public branding, project/course authoring
improvements, curated FAQ PDO persistence, photo gallery, generic credit service,
REST/token-auth support and generic AI transport. Additional recovered changes
bind security/AI lookups through dbTable and restore installer safeguards.

Validation included PHP syntax checks, sixteen focused navigation/content tests,
local transactional navigation and FAQ checks, 29 REST routing assertions,
photo-provider normalisation and native-auth query/permission regression checks.
No live mail, payment or AI-provider requests were made. A full fresh installation
was not repeated for the recovered installer changes.

## Required release discipline

Commit and push shared source before deployment. Assemble from explicit GitHub
commits, record those commits and file hashes in the release manifest, compare
the live files before replacement, and stop if an unexpected shared change is
found. Retain explicit private overlays separately. Verify deployed hashes and
health afterwards. The audit tags establish provenance for older compositions;
they do not remove the remaining need to consolidate older sites onto a tested
common release.
