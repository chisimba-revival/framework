# Configuration contracts and safety gates

Updated 10 October 2026: all identified first-party PEAR Config callers now use
native configuration documents. Site/properties XML, installer configuration,
module catalogue, user INI preferences, the general INI/ADM helper and canvas/RTT
callers are migrated. The native tree replaces Config_Container/PHPArray. The
historical `getObject('config', 'config')` service name resolves to `altconfig`
at the object factory; it does not require a global Config class.

Translation2/I18Nv2/MDB2 remain separate work. Vendor Config is retained for the
legacy comparison fixtures and external-extension/deployment audit; it is no
longer loaded by the tested engine and catalogue runtime. No vendor edits.

## Run

From the framework checkout:

```sh
php tests/configuration/configuration_safety_test.php
php tests/configuration/configuration_failure_test.php
php tests/configuration/configuration_concurrency_test.php
php tests/configuration/sysconfig_save_test.php
php tests/configuration/remaining_migration_test.php
php tests/configuration/preferences_controller_test.php
php tests/configuration/catalogue_refresh_controller_test.php
php tests/configuration/installer_configuration_test.php
php tests/configuration/catalogue_discovery_test.php
```

`legacy_configuration_contract_test.php --require-safe` is retained as an alias
for the repaired safety suite; it no longer blesses the original defects. All
commands require success. Each accepts an alternate application directory as its
first argument. For example, stream a test into the mounted local container:

```sh
docker exec -i -u www-data LOCAL_WEB_CONTAINER php /dev/stdin /var/www/html/ch < tests/configuration/configuration_failure_test.php
```

For the real ownership-failure gate, run `configuration_ownership_test.php`
as root **inside the disposable local container**. It creates a root-owned
fixture and drops its child process to www-data; it does not touch installed
settings or change any existing permissions.

All writes use random, disposable, mode-0700 temporary directories. The fixture suites do not
write installed settings or database records. The installer suite executes the
actual configuration/handoff step against a temporary root; it does not create a
full database installation. The discovery suite reads real source registrations
and publishes only to a temporary catalogue, with database reconciliation doubled.
`runtime_smoke.php` is a separate, opt-in installed-engine read-only check:
`CHISIMBA_EVENT_SMOKE=1 php tests/configuration/runtime_smoke.php /local/app`.
It checks engine boot, both configuration service names, catalogue, translation,
events and a constant SELECT, and asserts PEAR Config classes remain unloaded.
Fixtures are removed in finally. The concurrency test uses two actual PHP
processes and pipes; proc_open must be enabled. Run permission tests as a non-root
user (including the actual web user); the read-only-directory assertion is
explicitly omitted when root can bypass it. Diagnostics fail the tests.

## Implemented boundary

- `configurationfile.php`: same-directory exclusive temporary file, restrictive
  creation, full-write loop, checked flush/fsync/close and atomic rename. Existing
  uid/gid/mode are preserved or the write fails. New files are mode 0600. The
  original file is never unlinked or truncated before successful replacement.
- `configurationxml.php`: DOM XML parsing, UTF-8 strings in memory, source
  UTF-8/ISO-8859-1 encoding retained on disk. DOM emits character references for
  characters outside Latin-1. New documents use ISO-8859-1 for installation
  compatibility. DTDs/entities, namespaces (except the existing catalogue root schema-location attribute), mixed element/text content, invalid
  names, invalid UTF-8 and illegal XML characters fail before writing.
- `configurationdocument.php`: each destination owns its parsed tree, original
  bytes, encoding and revision. Successful writes refresh the cache. A failed
  setter never installs its candidate tree. Path changes cannot reuse another
  document's state; even a missing properties read cannot redirect a later save
  back into the preceding properties file.
- `altconfig`: whole replacement, append, generic setter, updateParam, properties
  and all 22 specialised setters use this boundary. Missing directives fail;
  append is the explicit creation API. Short-name, pre-login and DSN setters now
  target their intended fields. setDsn writes KEWL_DB_DSN; it does not rewrite
  separately configured CHISIMBA_DB_* connection fields. The old setter wrote the
  entire DSN into CHISIMBA_DB_PORT. getDsn's existing constant-based behaviour is
  unchanged, so a new request observes bootstrap changes.
- Sysconfig site editor: administrator gate, POST and canonical native CSRF
  checks, revision carried across HTTP requests, failure notice with retained
  entered value and original revision. Reload is required to accept newer state.
  Site saves do not fall through into the database module-parameter updater.
  Shared contextual Help documents access, steps and recovery. Database module
  parameter editing and its existing security boundaries are outside this stage.

Writes return false on checked failure; `getLastWriteError()` supplies a safe
message without settings/path contents. Invalid/missing site reads throw a safe
RuntimeException instead of printing parser details and terminating. Missing or
invalid properties reads return false. Application-wide error handling is not
suppressed. The filesystem wrapper locally consumes OS warnings only where it
checks return values and reports a controlled failure; it restores the caller's
error handler immediately.

## Concurrency and filesystem contract

A persistent `<destination>.lock` coordinates cooperating writers. It is never
unlinked because waiters must lock the same inode. Existing bytes must match the
reader's snapshot under the lock; the writer checks again immediately before
rename. Editors also carry a SHA-256 revision across requests. Stale saves fail
without replacing the newer file.

The destination directory must be trusted and on a local filesystem supporting
flock, same-directory atomic rename and fsync. The web user needs directory write
permission as well as enough privilege to preserve the file's ownership. If it
cannot preserve metadata, saving fails; do not broaden permissions as an automatic
repair. Sidecar locks are created private, so deployments using multiple writer
identities need an explicit ownership/locking policy. Symbolic-link destinations,
hard-linked destinations and linked/non-regular lock files are rejected.

Uncooperative writers do not honour this lock; the final comparison reduces but
cannot eliminate races with them. Directory fsync/power-loss durability, ACLs,
extended attributes, network filesystems and hostile changes to parent paths are
not claimed. No direct container write API should bypass the new boundary.

## Read compatibility

The root/Settings array shape, case sensitivity, last-duplicate lookup, ordered
duplicate arrays, section/directive attributes (`@`/`#`), empty strings, numeric
strings, literal FALSE strings, trimmed leaves and decoded entities are covered.
CDATA becomes text. Comments/formatting are not retained by this semantic editor.
Malformed input is rejected; unsupported source encodings are rejected instead
of silently rewritten. INI and PHPArray baseline observations remain in the
safety test, but do not certify unrelated legacy writers.

The original seven failures were stale replacement/append caches, broken generic
setter, unsupported-format deletion, false-success write failures, Unicode loss,
and cross-document destination contamination. They reproduced before changes on
both host and container. The Unicode gate exercises the native document boundary, not the unchanged
vendor XML parser. Historical vendor tests are not active configuration callers.

## Evidence

- 22 compatibility observations and all seven safety gates pass on host/container.
- 118 failure/roundtrip/setter checks pass on host and as the container web user:
  partial/zero/failed writes, failed flush/rename, cleanup, permissions/ownership,
  stale setters/append/replacement, stale HTTP revisions, malformed/unsafe XML,
  Unicode/Latin-1, duplicate/attribute roundtrips, empty/new documents, path changes,
  independent properties, links, all specialised setters and updateParam.
- Real root-owned fixture: the web user cannot preserve ownership, so the save
  is refused and original bytes/mode survive; no temporary file remains.
- Two real processes: one complete commit, one stale rejection, no temporary leak;
  passes on host and container.
- 19 actual site controller/form-helper checks pass with disposable configuration
  and service doubles: admin denial, GET/CSRF/revision rejection, preserved escaped
  draft, valid POST save and repeated-form rejection. No DB updater is allowed.
- Native event/tag-cloud and strict XML parser regressions, installer-handoff
  guards, PHP lint and diff whitespace checks pass.
- Local authenticated Chrome: site-name editor, rejected GET and POST with draft
  retention, quick/full Help, Escape/focus return, 390px layout and desktop reset.
  No successful write was made to installed settings. Read-only smoke passes for
  Sysconfig, Module Catalogue, Site Administration, Users, Groups, Class Admin and
  File Manager. No browser warning/error logs or visible PHP errors.
- Actual engine boot, translation lookup and constant read-only DB query pass.
  Installed config.xml/catalogue.xml hashes match the pre-migration baseline.

## Completed callers and additional evidence

- Native INI reads preserve the observed legacy boolean, comma-list and duplicate
  conventions. Writes are lossless UTF-8 strings with an explicit
  `; Chisimba configuration INI v1` marker and JSON-style quoted escaping.
  Quotes, commas, backslashes, newlines and literal environment-like text survive.
  Environment/constant expansion is intentionally disabled. Invalid UTF-8,
  multiline legacy quoted input, malformed quotes and nested INI sections fail
  closed rather than silently changing values. Comments/formatting are not kept.
- User preferences check owner/admin access on every operation, reject path
  traversal, preserve unrelated keys and write only the selected user's file.
  Reads of absent preferences do not create files. HTTP save/delete require
  POST, canonical CSRF and a document revision; failed drafts remain visible.
  Canvas redirects to this editor. RTT uses the preference service's scalar API.
- Catalogue discovery must be complete, nonempty and unambiguous. Native XML
  escapes metadata and preserves categories/schema attributes. Publication uses
  the checked writer; a refresh lock spans discovery, publication and subsequent
  database reconciliation. Failed/stale publication never reconciles. This is
  **not** a transaction spanning XML and the existing database reconciliation:
  a DB failure can leave a newly published catalogue and needs an explicit retry.
  HTTP refresh requires administrator access, POST and canonical CSRF; failures
  display recovery guidance. Both refresh controls use POST. Contextual Help is
  included. Existing module install/update operations were not redesigned.
- Installer configuration and credential files use checked replacement and mode
  0600 for new files. DSNs are quoted as PHP literals. Failed saves cannot be
  ignored; a valid config.xml determines completion. Each file is atomic, but
  the pair is not a filesystem/database transaction. The source installer is not
  overlaid in the local runtime, so container testing used a temporary source
  snapshot, subsequently removed.
- The generic helper retains named INI/XML/ADM operations; executable PHP input
  and arbitrary PEAR adapters are unsupported. No live generic-helper caller was
  found. External extensions using Config_Container's persistence methods need
  migration to documents before vendor removal.

Additional tests pass on host and as container www-data: 47 native migration
checks, 22 actual preference controller checks, 7 catalogue refresh action checks,
13 actual installer configuration/handoff checks, and all 200 real source
registrations published and read in a disposable catalogue. Together with the
original suites this is 255 assertions, plus real concurrency/ownership gates and
read-only runtime boot. Existing module-catalogue suites, event/tag-cloud contracts,
strict XML, authentication form-lifetime/installer guards, PHP lint and diff checks
also pass. The failure, concurrency, ownership and site-controller suites no longer
preload PEAR Config.

Final Chrome checks: catalogue search/installed filter; rejected GET refresh;
preference add/edit, rejected GET and POST with retained escaped draft; canvas
personal-choice handoff; preference and catalogue quick/full Help, Escape and
focus return; 390px preference editor without horizontal overflow; desktop reset;
Sysconfig, Site Administration, Profile, Groups and File Manager smoke. This
caught and repaired the historical configuration service-name failure and a
missing-module breadcrumb/layout defect. No successful installed preference or
catalogue write was attempted. Final browser console had no warnings/errors. Anonymous HTTPS requests to
preferences, catalogue and Sysconfig returned login forms without PHP errors.
Final log inspection found only earlier requests for the repaired config-alias
error page, with no new PHP warnings, deprecations or fatal errors.
Installed config.xml and catalogue.xml hashes still match the baseline.

## Cutover and completed local registration

Deploy the coordinated framework changes and RTT caller together. Apply normal
module updates for config 2.007, sysconfig 1.623, modulecatalogue 3.140,
userparamsadmin 0.812, canvas 0.047, toolbar 1.812 and rtt 1.1010. No schema change
is introduced by this migration. Back up configuration/preferences before release.
**Rollback must restore the matching INI backups as well as old code**: the old
PEAR reader does not implement the new marker's full escaping contract. Do not
roll back the reader alone after native preference writes.

User Configuration 0.812 was registered locally through the normal module service
on 10 October 2026 after explicit user approval. The earlier automatic-review
block is resolved. No custom database write or permission workaround was used.
The registered browser checks pass: correct breadcrumb without the missing-module
notice; list/add/edit; quick/full Help and Escape/focus return; rejected GET and
POST saves retaining the draft; canvas personal-choice handoff; 390px editor and
desktop reset. The native runtime and 22 preference-controller checks pass again.
Anonymous HTTPS access returns the login form. Recent container logs contain no
PHP warnings, deprecations, fatal errors or class-loader diagnostics. Site and
catalogue file hashes remain unchanged.

Successful preference mutations and database reconciliation were verified with
disposable fixtures/doubles, not against personal settings or live module records.
A complete fresh database installation remains a release check.

No production deployment or commit was made. Framework/modules branch:
`release/consolidation-20260930`. Existing unrelated work remains intact. Passing
these gates does not claim compatibility with uninspected external extensions,
all deployment filesystems or every historical PEAR Config format.

## KengaLearn release filesystem gate (10 October 2026)

The production-matching default ACL fixture exposed that `umask(0077)` alone
can create mode-0660 files on an ACL-enabled directory. The writer now explicitly
sets mode 0600 on the empty temporary file before writing bytes, and on its lock.
Existing configuration mode/owner/group preservation is unchanged. Run
`php tests/configuration/configuration_acl_test.php` on a filesystem with POSIX
ACL support and `setfacl` installed. This checks private new files/locks under
inherited ACLs and preservation of an existing file's mode.
