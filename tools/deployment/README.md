# One common development codebase

This is an active development workflow, not a stable-release channel. The common
branch is `release/consolidation-20260930` in both framework and modules. Merge
completed feature work there, test, commit and push before deploying. Do not deploy
from feature branches, working trees, per-site snapshots or copied live source.

`source_manifest.py build` requires each local HEAD to equal the actual GitHub
branch tip. It reads Git archives, never working files, and creates one source
bundle and SHA-256 manifest for every site. Site settings, content and selected
canvas remain independent of the shared source. All public canvases ship together. `module-profile.json` is the single fleet-wide
application-module inventory, including the union of currently used applications.
Add a new shared application there before deploying it; archived/unmaintained
modules are not reintroduced merely because their historical source exists in Git.

```
python3 tools/deployment/source_manifest.py build \
  --framework /path/to/framework --modules /path/to/modules \
  --output /path/to/new-bundle
python3 tools/deployment/source_manifest.py verify \
  --root /path/to/runtime --manifest /path/to/manifest.json \
  --manifest-sha256 APPROVED_MANIFEST_HASH
```

The deployment operator must check the complete existing source against its last
manifest, stage the complete new bundle (remove obsolete source), rehearse module
updates on an isolated database clone, back up production, switch the code and
verify the same manifest again. Keep mounts read-only. Never silently adopt an
unexpected live edit as a new baseline. Reconcile it into the common GitHub branch
or remove it after review. The initial November consolidation has a separately
reviewed migration baseline because previous deployments lacked full manifests.

MyHairPlanner is the only private-code exception: its private modules and REST
adapter are declared by exact paths and hashes in a private overlay manifest.
Do not place those files or their content in either public repository. Private
paths are also checked for changes; the exception is not a blanket exclusion.
Configuration, uploads, sessions and generated HTMLPurifier caches are outside
the shared source manifest. These still require normal operational backups.

The verifier detects missing, changed and extra source files, including extra
root PHP scripts and symlinks. It rejects a substituted manifest unless its
expected hash is also explicitly changed by the deployment process. This guards
the normal deployment path; administrators with host access can still bypass it,
so recurring read-only fleet verification is required. Report drift rather than
automatically overwriting it. A temporary rollout in progress must name its
intended commits; otherwise all sites must match the common manifest.

Run `python3 tools/deployment/test_source_manifest.py` after changing the checks.
