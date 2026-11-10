# Common development source — 10 November 2026

This supersedes the provenance-only audit earlier that day. Sites use a common
active development codebase, not a stable-release channel. New development can
be committed and deployed repeatedly; each deployment records exactly which
pushed revisions it uses.

The consolidation merges completed API/course-maintenance work from the prior
integration branch into `release/consolidation-20260930`. Previously that work was
published but deployed separately, while later maintenance was applied as small
per-site overlays. Both practices allowed sites to diverge.

All sites receive the same 13,530-file shared source bundle and the same maintained
application-module inventory. Unused legacy source is removed from active releases;
old releases and databases remain backed up. Installed module choices, branding
selection, configuration, content and uploads remain site-specific data.
MyHairPlanner's private modules and REST adapter are a separately hashed exception;
their code is not in the public repositories. No other site has a source exception.

## Upgrade safety

The common source was rehearsed against isolated clones of all seven databases,
using each site's actual PHP 8.5.12 container image. Rehearsals run registration
updates and hooks twice, exercise public home/login/recovery pages, and compare
protected business-table and existing-navigation fingerprints before and after.
Production switches repeat these checks with workers paused, verified database
and configuration backups, read-only source mounts and retained prior releases.
No live mail, payment or AI request is part of validation.

The first rehearsals exposed an obsolete Group Administration migration which
could delete and recreate users for the retired LiveUser model. It was removed
from normal upgrade hooks; canonical catalogue/group services now remain the
sole owners. A regression test checks that the hooks make no identity/persistence
calls. An API test helper's implicit nullable arguments were also corrected for
PHP 8.5. These findings were corrected before production deployment.

## Keeping development consistent

Use [the deployment tooling](../../../tools/deployment/README.md). Source assembly
requires pushed common-branch commits and reads Git objects, not working trees.
The complete manifest detects edits, missing files, extra source and unexpected
source mounts. Fleet verification checks actual file bytes inside running
containers as well as host files, and reports newer shared runtime changes on
the common GitHub branch. Documentation/tool-only commits do not require a
runtime rebuild when their shared runtime source is unchanged.

The current approved fleet is recorded in
[the fleet inventory](../../../tools/deployment/fleet.json). Update it only after
an authorised, verified rollout, never merely to silence unexplained drift.
A daily 08:00 read-only Codex check reports new drift/check failures without
changing production. These checks support active development; they do not freeze
features or create maintained per-site branches.

## Pinned runtime source

- Framework: `d158c7f58e505e499e5086e1676bc8fe4fd2792a`
- Modules: `3f563c4f81c39f6e18256950d899c029b2501d91`
- Common manifest SHA-256: `259a455e9097d3327136ac28c8e7da077a2d495caf4c7d9ff6028d06c7190187`

Later documentation/audit-tool commits retain identical shared runtime source.
