# Current status — 12 October 2026

PDO MySQL is the sole supported backend. Run `python3 tests/database/run-pdo-contracts.py`.
The runtime PEAR directory and MDB2 recorder have been removed.
`mdb2-baseline.json` preserves result semantics; `schema-baseline.json` preserves
real MDB2 column definitions captured using MariaDB 10.11.18 before retirement.
The disposable PDO runner validates those contracts without loading any PEAR code.
The dated notes below describe earlier migration stages, not current deployment.

# Database migration baseline

Run from the framework repository, with Docker access:

```sh
python3 tests/database/run-mdb2-baseline.py > /tmp/chisimba-mdb2-baseline.json
```

An optional image argument selects a local PHP image. The runner creates a random,
internal Docker network and disposable MariaDB 10.11.18 database in tmpfs, with no
published ports. It never reads installation credentials or uses an installed site
database. Resources are removed on exit. All data is synthetic.

The PHP fixture uses the real dbTable class and MDB2/mysqli implementation with
engine-equivalent fetch/portability settings. It bypasses only framework object
construction/configuration and keeps caching disabled. It asserts quoted Unicode
roundtrip, application IDs, empty sets, one-query limit/offset, commit/rollback and
generated-ID shape. Other observed results are recorded for migration decisions.

`mdb2-baseline.json` was captured on 11 October 2026 with PHP 8.5.11, MariaDB
10.11.18 and framework source 5cacea01d. Notable actual behaviour:

- Numeric column values and record counts are strings; write counts are integers.
- Missing row is null; an empty result set is an empty array.
- Column aliases are lower-cased; the empty-string fixture is returned as null
  under the current MDB2 portability configuration.
- A no-op update and a missing-row update both affect zero rows.
- Public update/delete currently return MDB2 result objects, while query(write)
  returns an affected-row count. Audit consumers before changing that inconsistency;
  do not emulate obsolete result classes merely to match this observation.
- getLastInsertId identifies the application's id, not auto-increment puid.

This is the first baseline, not a complete acceptance suite. Extend it for bindings,
failures, transaction ownership, schema operations and direct consumers before
switching the installed local application to PDO. The driver prerequisites are
separately tested in dev-environment/tests/pdo. No application backend has changed.

## Native shared boundary — local implementation, 11 October 2026

```sh
python3 tests/database/run-pdo-contracts.py
```

The runner uses the same isolation rules as the MDB2 recorder. It runs both the
native dbTable parity fixture and real engine getDbObj/dbTable initialisation
composition. The latter substitutes only fixture configuration/object setup and
search bootstrap; it does not boot or migrate a complete installed PDO site.

The native service lives in `app/classes/core/chisimbadatabaseconnection.php`.
It uses native prepared statements, exact associative values, checked affected
counts, cursor cleanup and redacted exceptions carrying SQLSTATE only. Sensitive
arguments are masked in PHP exception traces. DSN conversion handles mysql/mysqli,
host ports, bracketed IPv6 and Unix sockets; default charset remains utf8. PostgreSQL
and other drivers are explicitly unsupported in this first implementation.

The engine's PDO branch now composes one canonical service shared by subsequent
connection requests. The installed local configuration still selects MDB2.
`dbTable` handles ordinary reads/writes, limits, application IDs, substring
expressions and table listing through the native service when selected. Legacy
row normalisation stays in dbTable: lower-case keys, scalar strings, initial empty
strings to null and then right trimming. In particular, whitespace-only strings
become empty strings, not null; that order is verified against real MDB2.

Native dbTable update/delete return their documented success booleans (including
successful zero-row writes), not obsolete MDB2 result objects. query/_execute
return actual affected counts. Callers relying on result objects still require
migration review. Parameterised _execute and CRUD now bind values without double
quoting; errors return false at the dbTable boundary with redacted diagnostics.
Direct native consumers receive ChisimbaDatabaseException and must handle rollback
and their own domain errors explicitly.

Transaction generations prevent another dbTable instance, or a stale previous
owner, from committing/rolling back the current owner's work. Nesting is rejected;
there are no implicit savepoints or automatic retries. DDL implicit commits remain
a separate schema concern. Native sequence allocation, changing databases on the
canonical connection and the old stored-procedure API explicitly reject use;
no active first-party callers of those helpers were found in the prior audit.
They are not silently emulated. The private legacy bulk helper remains MDB2-only.

Passing evidence:

- MDB2 recorder unchanged for existing cases; extended whitespace baseline captured
  from the actual legacy driver rather than inferred from the new implementation.
- Native parity plus exact native whitespace/Unicode/null values, positional/named
  bindings, injection-shaped values, no-op/stale write counts, duplicate keys,
  read failures, credential/SQL redaction (also with trace arguments enabled),
  pagination, IDs, table lists, substring helpers, transactions and advisory locks.
  A deliberately disconnected fixture session fails commit explicitly and its
  uncommitted writes are absent from the surviving connection.
- Native connection/dbTable tests do not load PEAR or MDB2. Engine composition loads
  the existing generic PEAR error-handler dependency but does not load MDB2.
- Real engine composition reuses the same native connection across engine objects
  and initialises a real dbTable instance successfully.
- Installed local MDB2 iterator, translation and configuration/event smoke tests
  passed, along with the existing MDB2 native-auth adapter unit tests.
- Authenticated Chrome home, Kanban and Knowledge Maps read checks passed; browser
  warning/error capture was empty. Existing PHP compatibility warnings remain:
  345 cumulative post-preparation lines, zero new normalised signatures compared
  with the saved pre-preparation log, and no fatal/uncaught errors.

These are shared-layer gates, not full migration acceptance. Security repositories,
translation writes, direct module stores, schema/installer/operator paths and full
PDO browser journeys are still pending. Do not switch the installed site to PDO,
remove MDB2, deploy, commit or push on the strength of these tests alone.

### Authentication caller migration — first slice (2026-10-11)

`auth_database` now supplies its existing dbTable instance to
`DbTableNativeDatabaseAdapter`. Its former MDB2 adapter has been removed, and
both adapter/provider regression tests exercise the replacement. The domain
repository interface stays unchanged. No separate PDO authentication repository
was introduced.

`dbTable::selectPrepared()` and `executePrepared()` accept positional parameters,
throw on database failure, and return legacy-compatible rows or integer affected
counts. They bypass read caches, retain zero affected rows for compare-and-swap,
and free MDB2 statements/results. They deliberately retain the old empty-string,
whitespace and alias-case contract; they are not an exact-value translation API.

Both disposable runners include `prepared_repository_checks.php`: Unicode and
quoted values, SQL injection rejection, compare-and-swap reuse, duplicate errors,
row portability and real password-provider success/wrong-password/inactive-user
cases. The installed read-only composition check is:

```sh
docker exec -i -e CHISIMBA_AUTH_DB_SMOKE=1 chisimba-php85-web php /dev/stdin /var/www/html/ch < tests/database/authentication_runtime_smoke.php
```

This does not create a session or change users. Browser home-page checks exercise
an existing authenticated session, not a fresh end-to-end login.

Remaining security/translation work: MFA, remembered-login rotation and abuse
repositories still use MDB2. Their unchecked transaction calls need checked
ownership and failure tests before migration. Translation also needs a deliberate
shared boundary for advisory locks and schema management. Neither this slice nor
the PDO foundation enables the installed application's PDO backend. Full local
acceptance and removal of remaining MDB2 dependencies are still pending.

### MFA, remembered-login and abuse persistence (2026-10-11)

The next slice replaces Mdb2MfaRepository, Mdb2PersistentLoginRepository and
Mdb2AbuseEventRepository with dbTable repositories and removes the former files.
Both composition boundaries now initialise through dbTable. The factory accepts
a dbTable, never a driver connection. Domain interfaces and schema are unchanged.

`dbTable::transaction()` checks begin/commit/rollback, refuses existing
transactions before invoking the callback, and prevents the legacy dbTable
transaction methods from ending an active callback transaction. The native
connection also checks its transaction generation. Callbacks must not execute DDL
or use driver connections to manage transactions. A rollback failure is logged
without replacing the original failure; commit failure never returns success.

`security_repository_checks.php` runs under both disposable drivers and covers:
MFA enrolment and replay, single-use recovery codes, rollback after partially
replacing recovery codes, remembered-token rotation and revocation, forced
failure after inserting a replacement token, foreign/nested transaction refusal,
abuse counts/budgets and lock release. Separate PHP processes race for the same
recovery code and remembered token; exactly one succeeds. A real connection is
killed between write and commit; the operation fails and a separate observer
sees the original value. This last test destroys only its disposable connection.

Abuse admission retains database-scoped advisory-lock SQL through dbTable's
prepared API. The lock is justified: it serialises budget checks across PHP
workers without nesting the caller's transaction. Lock release is now checked.
This is database-specific coordination, not a reason for a separate MDB2/PDO
repository. Translation's locking/schema requirements remain a subsequent slice.

Validation: both database runners, 13 focused existing native-authentication
regressions, installed read-only authentication/factory/MFA checks, and browser
home/My Administration/Registration protection pages. Browser checks used the
existing session; full fresh-login/MFA browser acceptance under PDO is pending.
The installed site remains MDB2; nothing has been committed or deployed.

### Remaining ACL/toolbar callers (2026-10-11)

Permissions ACL direct-identity lookup and toolbar record/module lookup now use
bound dbTable queries. Their private driver-quoting helpers are removed. ACL
user/group removal now binds both subject and ACL; the former code embedded an
extra SQL condition inside a single delete value, incompatible with safe binding.
Deletion success remains true when no rows match. Lookup failures grant no ACLs.

Both real-driver runners execute permissions_toolbar_checks.php before the
connection-loss tests: stored IDs containing quotes, numeric permission identity,
ancestor groups/cycles, unknown users, isolated user/group removal, hostile ACL
values, registered-only menus and targeted menu reads/edits/deletes. Seven existing
permission/toolbar tests also pass. Read-only local browser checks cover the
administration page, navigation editor and individual record, and ACL page.
The navigation editor has missing local registration labels (documented in the
schema requirements); this is not a clean UI acceptance result. No installed
permissions or menu records were changed.

The first visit to the legacy ACL template exposed an obsolete assignment-by-
reference warning. Removed its two unnecessary reference assignments; reloading
preserved the page and produced no further reference warnings or fatal errors.
Other historical PHP warnings remain outside this slice.

### Shared classification persistence (2026-10-11)

Classification storage now uses bound, exact-value dbTable queries throughout;
its direct driver quoting and execution are removed. Vocabulary row locking is
retained to serialize competing writers. The shared transactionWithSavepoint()
method preserves an enclosing transaction, including one started by legacy raw
SQL. It owns a checked transaction when none exists, otherwise only its own
savepoint. Callbacks must not execute DDL or manage transactions themselves.

Both full database runners pass classification_checks.php: tag deduplication,
quoted identities and scope isolation, hierarchy/deletion constraints, provider
visibility, partial-batch rollback, enclosing rollback/commit ownership, and two
separate workers creating one shared term with both item associations retained.
The schema guard now requires the ID-only unique index, rejecting a misleading
composite unique index. All writes and schema alterations use disposable tables.
The existing classification service regression also passes.

classification_runtime_smoke.php boots the installed service through the real
engine and performs read-only absent-vocabulary queries. Run with
CHISIMBA_CLASSIFICATION_SMOKE=1 and the installed application path as argv[1].
This check passed on the installed MDB2 site. The authenticated local home page
loaded with an empty browser warning/error capture and no new PHP log signatures
against the preceding ACL-stage snapshot. Historical PHP warnings remain.
Classification has no standalone page; this is not end-to-end classification UI
acceptance. Installed PDO acceptance remains pending the remaining callers and
schema/installer migration. No installed classification records were changed.

### Local activation and remaining callers (11 October 2026)

See [acceptance evidence](../../docs/database-migration/local-acceptance-20261011.md)
for the module, schema, restored-site and browser results. Both runners now include
`schema_checks.php`, `module_repository_checks.php` and
`catalogue_upgrade_checks.php`. The PDO runner separately exercises installer
schema/seed composition without MDB2. Legacy bound empty strings must be stored
as SQL NULL; the exact APIs preserve literal empty strings.

`local_catalogue_rehearsal.php` is an explicit opt-in, local-only mutation script:
it updates five named modules through Catalogue and checks protected data
checksums. Rehearse on a restored private copy and retain a backup before using it
on the installed local site. It is not a generic production upgrade command.

`content_expiry_checks.php` exercises the Utilities notification API against real
content and Communications outbox fixture tables on both drivers. It verifies
canonical recipients/site sender, recipient rejection, a database-triggered queue
failure, failed timestamp persistence followed by deduplicated retry, a 24-hour
cooldown and a later notification. No transport runs and no email is sent.
