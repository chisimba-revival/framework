# Rehearsed source-switch operator

These operators implement the November 2026 common-development consolidation.
They require reviewed generated inventory beside them: sites, before hashes,
common source manifest/hash, private overlay hashes, per-site changed-file
manifests, expected module versions and runtime module inventories. Copy the
parent `source_manifest.py` alongside the operator when assembling its private
remote working directory. Never commit database clones, runtime config or backups.

Prepare uses an internal-only Docker network, a disposable database clone and
separate configuration/sessions/storage. It runs catalogue registration updates
and their hooks twice, then checks protected business rows and navigation before
and after. Pending XML schema operations stop for review. It does not run workers.
Deploy requires successful rehearsal evidence, unchanged live baseline, matching
candidate source and identical container image. It pauses affected timers/writers,
backs up the database and configuration, switches an immutable source directory,
updates registration, checks protected rows and restarts. Failures retain recovery
records and stop for diagnosis; never restore a database over later user writes.

Runtime content mounts are not copied or replaced by this source operation.
The normal site backup system continues to own uploaded-content backups.
For subsequent development deployments, require the previous common manifest as
the baseline rather than generating a new baseline from unreviewed live files.
