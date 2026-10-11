# Local PDO preparation — 11 October 2026

Status: driver preparation complete; application migration has not started. No commit/push or production changes are authorised during
local preparation. The first later production rollout target is chisimba.com.

Starting source: framework 5cacea01d, modules 4ecec6d65,
dev-environment e234025. All three working trees were clean before this work.

The running local stack is chisimba-php85: PHP 8.5.11 with only the SQLite PDO
driver, and MDB2_Driver_mysqli as the active application connection. It has 95
registered modules. Source classes/core_modules/skins and the modules repository
are bind-mounted over the assembled runtime. The database uses a separate Docker
volume and is not recreated by the web-image update.

Preparation adds pdo_mysql beside mysqli in the PHP 8.5 Dockerfile. The old running
image is retained locally as chisimba-php85-web:pre-pdo-20261011. The application
backend setting remains MDB2. No installer or module update is part of this step.

Evidence recorded before replacement:

- Installed translation/locale read-only smoke passed.
- Installed MDB2 iterator constant-query smoke passed.
- Local Chrome authenticated home rendered successfully.
- Disposable MariaDB/dbTable baseline passed; observations and runner are in
  ../../tests/database/README.md and mdb2-baseline.json.
- PHP image security and upload-limit source checks passed.

Completed checks after building and replacing only the web container:

- PDO drivers are now sqlite and mysql. Extension-set comparison confirms only
  pdo_mysql was added and all previous extensions, including mysqli, remain.
- The isolated PDO/MariaDB readiness fixture passed prepared values, Unicode,
  null/associative results, commit/rollback, second-connection visibility,
  compare-and-set counts, duplicate-key errors and advisory lock exclusion/release.
- Database container identity, all 95 module registry entries and MDB2_Driver_mysqli
  as the active application backend are unchanged.
- Installed translation/locale and MDB2 iterator smoke checks passed again.
- Chrome authenticated home, profile with country list and Knowledge Maps passed;
  the login session survived replacement. Browser warning/error capture was empty.
- Post-update logs contained legacy warnings/deprecations, all matching signatures
  in the preserved pre-update log (12,633 historical lines). At comparison time
  there were 187 post-update lines, zero new signatures and zero fatal/uncaught
  errors. Existing session-ini and dynamic-property warnings remain separate debt;
  this is not a claim of clean logs.
- Disposable fixture containers and the temporary session-transfer copy were removed.
- PHP lint, Python syntax and repository whitespace checks passed.

The initial compose build was interrupted because its package-download network
path was extremely slow. The same Dockerfile built successfully with:

```sh
docker build --network=host -f docker/php85/Dockerfile -t chisimba-php85-web .
```

This build-only network override did not alter the running services' network
configuration. Image manifest: 0d12891759b8b4a62c5db090963636d3ddd47d5013557c2e81286fccb7c3ab59.
Local diagnostic files from the old container are retained privately under
work/pdo-local-20261011/previous-tmp outside the repositories.

No production site, application backend, schema or module version was changed.
All source changes remain uncommitted as requested. The next step is to extend
shared database contracts and implement the native PDO boundary against disposable
local fixtures before enabling it in the installed local application.

## First direct authentication caller migrated

Primary password authentication now uses its inherited dbTable through
DbTableNativeDatabaseAdapter. Driver-specific prepared-query mechanics moved
into checked dbTable methods; the former Mdb2NativeDatabaseAdapter was removed.
The shared domain interfaces are unchanged. See tests/database/README.md for
contracts and fixture coverage. MFA, remembered-login rotation, abuse protection
and translation remain migration work; the installed backend is still MDB2.

## Security repository follow-up

MFA, recovery codes, remembered-login rotation and abuse events now persist via
checked dbTable APIs. The three corresponding MDB2 repository files are removed.
Disposable MDB2/PDO tests cover rollback, competing token use, and connection
loss before commit. Installed composition and browser read checks passed without
new PHP diagnostic signatures. Translation and other direct callers remain;
installed PDO acceptance and commits are still deferred.

## Translation persistence follow-up

Translation ordinary persistence now uses exact-value prepared dbTable methods;
its direct MDB2 calls are removed. Shared canonical-connection locking preserves
the existing lock name. Explicit schema operations use ChisimbaDatabaseSchema;
this does not yet replace the catalogue/installer manager. Both isolated drivers
pass translation lifecycle, rollback, text preservation and mixed-driver writer
contention checks. The installed read-only comparison matches all 7,328 English
translations. No installed language table or text was changed.

## ACL and toolbar follow-up

Removed direct driver quoting from ACL identity lookup and toolbar storage.
ACL deletion now binds both ACL and subject, replacing embedded SQL in a value
argument. Real MDB2/PDO fixtures and seven existing permission/toolbar tests pass.
Browser administration, menu record and ACL reads passed without saved changes;
missing navigation labels are recorded in schema-requirements-20261011.md.
An obsolete permissions-template reference warning discovered during the browser
check was fixed and verified by reload. The current local registry still contains
95 modules on MDB2. The caller audit includes untracked migration source now.

## Shared classification follow-up

Classification persistence now uses shared dbTable prepared queries and checked
savepoints instead of direct MDB2 calls. Vocabulary locking and enclosing
transaction ownership are preserved. Both full disposable MDB2/PDO suites pass,
including failed-batch rollback and two-worker classification contention. The
existing service regression and installed read-only service composition pass.
Local authenticated home browser smoke passed; console warnings/errors were
empty, with no new PHP diagnostic signatures against the prior ACL snapshot.
Classification has no standalone UI, so consumer workflow browser acceptance
will accompany the remaining module migrations.

No installed records or schema were changed. The site remains on MDB2 and all
migration source remains uncommitted. Next are the remaining module callers,
starting with Kanban, Knowledge Maps and Question Generator, followed by the
catalogue/installer schema boundary and full local PDO browser acceptance.
