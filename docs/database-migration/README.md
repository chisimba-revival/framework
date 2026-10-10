# MDB2 to PDO: assessment and staged plan

Assessment date: 10 October 2026. **Planning only; no application, database,
container or production configuration changes were made by this assessment.**

## Recommendation

Preserve the public `dbTable` API and replace its database implementation with
native PDO-backed services. Migrate direct MDB2 callers explicitly, including
security repositories and schema management. Do not flip the existing PDO
configuration switch: that branch does not meet the current application's contracts.
Do not build a permanent implementation of the entire MDB2 API.

First supported target: KengaLearn's MariaDB/MySQL environment. Historical
PostgreSQL/Oracle support requires a separate acceptance decision and tests.
Language administration and reviving Activity Streamer are separate feature work;
include their shared database dependencies in the audit, without enabling them.

## Evidence and boundaries

- Source baseline: framework `2bf397a8e45c0aba3f10db482602a0abd8c23d35`,
  modules `4ecec6d65b29d6b6b5886602d40eb0af506a3357`.
- [Inventory summary](inventory-summary.json): 9,972 tracked PHP-containing files
  scanned, including PHP in `.inc` and `.sql` files. Comments and inline HTML
  are masked before matching. String literals remain candidates.
- [Line-level candidates](callers.csv) and [file/owner map](candidate-file-map.csv):
  26 framework and 77 module application-file candidates for direct dependencies.
  These **103 files are not 103 proven database callers**. Domain service aliases,
  configuration, package declarations and historical tools also match.
- 454 application files declare a `dbTable` subclass (95 framework, 359 modules).
  This is an exposure measure, not the number of files needing edits. Most should
  continue using the shared interface. 73 application files contain database
  connection/management getter signals, including definitions.
- [Read-only KengaLearn snapshot](kengalearn-runtime.json): PHP 8.5.4; PDO drivers
  **SQLite only**; MariaDB 10.11.18; 78 registered modules; 198 tables, all InnoDB.
  185 tables use utf8mb3_general_ci and 13 use utf8mb4_unicode_ci. Isolation is
  REPEATABLE-READ; strict SQL mode is recorded in the snapshot.
- The production PHP image therefore needs **pdo_mysql** before a switch can work.
  Test the exact proposed release image, not just a developer's PHP installation.
- Source HEAD is not the live release manifest. Live is the bounded translation
  release `release-translation-20261010-145828`, based on framework
  `6e101d2ee1995d4170a7055684bdfe10e25fc5c0` and modules
  `9cdde04de1e9fff5d38c0e403fa0d44a59d040b6`, plus the translation changes.
  Reconcile intervening changes before building a release.

Registration is evidence of exposure, not usage or reachability proof. Unregistered
modules can supply shared code. Static patterns cannot resolve every dynamic
alias, generated query, external extension or deployed-only file. Embedded library
and `resources` paths are counted separately in the summary and excluded from the
line CSV; that exclusion **does not establish that they are unused**. Review their
reachability before deleting dependencies. The scan does not connect to external
module databases or inspect user content.

Reproduce from the framework repository:

```sh
php tools/audit-database-callers.php ../modules /tmp/chisimba-database-inventory
```

The tool reads tracked working-tree contents and records HEAD identifiers; it does
not certify a clean checkout. Record working-tree status alongside future runs.
The scanner excludes itself to avoid matching its own pattern declarations.

## Reviewed requirements

Paths below are relative to their respective repositories. The CSV provides exact
file paths and candidate line numbers. These are implementation requirements,
not assurances that the current code already satisfies them.

| Area | Evidence / important callers | Required treatment |
| --- | --- | --- |
| Connection lifecycle | framework `app/classes/core/engine_class_inc.php` | Normalise legacy mysqli DSNs to PDO mysql; preserve host, port/socket and credentials; explicit fetch/error/encoding options; one canonical connection per request; redact connection failures. Existing PDO branch builds its prefix from phptype and omits port/socket handling. |
| Shared queries | framework `app/classes/core/dbtable_class_inc.php` | Preserve row shapes, lower-case keys where required, empty results, limits/offsets, affected counts, quoting and caller-visible errors. Existing PDO fetchAll defaults differ from associative MDB2 results. Unconditional setLimit/supports/nextId calls and in_transaction access prevent a switch. |
| IDs and binding | same dbTable class | Preserve application-generated `id` and cached getLastInsertId semantics, separately from auto-increment `puid`. Inventory generateId/sequence consumers. Repair parameter execution deliberately: `_execute` currently ignores supplied parameters; avoid double quoting prequoted updates. |
| Transactions | dbTable and modern service stores | Explicit begin/commit/rollback results and ownership. A nested caller must not commit a caller's transaction. Define rejection or savepoint semantics from each contract, rather than silently changing behaviour. |
| Authentication | security nativeauth MDB2 adapter, MFA and persistent-login repositories, composition factory; abuseprotection repository/provider | Preserve interfaces, replace every construction path. The minimal adapter interface already specifies fetchOne/fetchAll/affected-count execute, but MFA, persistent login and abuse repositories bypass it. Three-argument MDB2 prepare and result objects cannot receive raw PDO. Preserve single-use recovery codes, replay protection, token rotation and fail-closed errors. |
| Permissions | permissions_acl and shared dbTable stores | Verify permission reads, membership scoping and denied actions on the canonical connection. Authentication success alone is insufficient. |
| Translation | language `translationstore_class_inc.php` | Replace driver-property checks, typed quote, result/schema calls and PEAR errors. Preserve same-connection GET_LOCK/RELEASE_LOCK ownership, release on exceptions, checked writes and nested-transaction refusal. Preserve installed English fingerprints and multilingual rollback tests. |
| Question Generator | workshopstore, examstore, workshopservice and install hooks | Explicit utf8mb4 session use; typed quote; redacted query failures replacing PEAR error handlers; FOR UPDATE, claim/affected-count checks, import-once and paid-generation concurrency. Never exercise paid external calls during database verification. |
| Registration and account services | registration cleanup/identity/token/service; membership, payment, entitlement, certificate, account-event and legal-acceptance stores | Prepared statement modes, token lifetimes, transactions and idempotency. Require concurrent duplicate/zero-affected tests. Payment/event delivery must not be replayed against external systems. |
| Ordinary direct getters | kanban, knowledgemap, pagenotes, contextcontent, mcqtests, ingestservice, sitepages, myadmin, systemmanagement, toolbar and others in CSV | Review quotation and connection assumptions even when only a q() helper is used. Several helpers probe quoteSmart then fall back to manual escaping: a connection change could silently select a different path. Prefer bound values/shared quote boundary. |
| Maintained but unregistered modules | events, shop, contactform, host-service, audience, liveclass, webinar, discussion, notifications, announcements, simpleblog | Migrate supported stores before enabling them on PDO, or explicitly gate unsupported activation. Events/shop need real oversell/idempotency and transaction tests; install hooks also use MDB2 schema APIs. |
| Schema and catalogue | dbtablemanager; modulecatalogue/modulesadmin; mcqtests, questiongenerator, gradebook and other install hooks | Native MySQL schema service for create/alter/drop/list/index/constraint operations. Preserve `puid` versus application `id`, defaults, lengths and indexes. Verify actual outcomes: legacy methods sometimes return true or a truthy message after failure. |
| Installer | `app/installer/dbhandlers/*.inc`, steps/databasedetails.inc, versioncheck.inc | Replace MDB2 factory/Schema and driver detection; prove a fresh disposable install as well as existing-site upgrades. |
| Packaging and operators | framework package-config.php; dev-environment PEAR package manifest/build scripts; tests and CLI scripts in inventory | Update build requirements, probes, fixtures and operator tools. An HTTP-only migration does not justify removing MDB2. |

### Candidate dispositions requiring care

- Context/newestops, login/nonce, comment, commenttypeadmin, contentblocks,
  maillist, textblock and sitepages/controller contain domain-service aliases
  resembling database objects. Do not mechanically rewrite these matches.
- Customexception includes an MDB2 label; altconfig and package-config include
  configuration/dependency declarations. They are not ordinary SQL callers.
- `faq26` has a legacy escapeSimple fallback requiring an escaping review.
- Modulecatalogue/pofile uses fetchRow in an old export path; reconcile its expected
  result with dbTable's array-returning query contract before declaring it supported.
- Blog importer opens a separate external MDB2 database. Decide support or retirement
  explicitly; do not test it against unknown external databases.
- The phpunit module contains legacy analyser/generator references. Treat those as
  developer tooling, not evidence of an active production database path.
- Bundled Auth/OpenID MDB2 integration and other embedded alternate database
  libraries need a reachability/licensing decision in the final cleanup audit.
- Activity Streamer is primarily a dbTable consumer, with a contextcontent bridge
  also present in the candidate map. Migration does not itself revive its UI.

## Implementation sequence and acceptance gates

Each step should be a small reviewed commit with its evidence recorded. Keep the
live site on the known-good MDB2 release until the combined release passes.

1. **Freeze contracts and prepare the image.** Capture representative real MariaDB
   fixtures against MDB2, build an image with pdo_mysql, and verify DSN/connection
   behaviour. Record the supported-module list, deployed/source differences and
   current worker entry points. Gate: reproducible image and baseline fixtures;
   no live switch.
2. **Implement the shared PDO query boundary and dbTable integration.** Explicit
   parameter binding, result conversion, IDs, limits, errors and transaction ownership.
   Keep backend selection available only for controlled comparison during migration;
   one selected backend/connection per execution, never dual writes. Gate: equivalent
   supported results on independent disposable databases plus negative-path tests.
3. **Migrate security and native translation.** Cover every factory/provider and
   direct repository; preserve their existing public interfaces. Gate: authentication,
   MFA, remembered-login races, permissions, translation rollback/concurrency and
   English-output parity on real MariaDB, not just repository doubles.
4. **Migrate direct module callers.** Start with registered KengaLearn modules, then
   maintained unregistered stores. Re-run inventory and assign a disposition to
   every direct candidate. Gate: each supported module's critical reads/writes,
   isolation, concurrency and error handling pass; unsupported activation is explicit.
5. **Migrate schema, installation and operator paths.** Exercise normal module
   registration/upgrade twice, fresh install, indexes/defaults and deliberate failures.
   MariaDB DDL can implicitly commit: do not promise a transaction can undo an entire
   upgrade. Gate: schema comparison and restore rehearsal on disposable databases.
6. **Rehearse and release to KengaLearn.** Run the full matrix below against the exact
   release image and a protected clone with external delivery disabled. Prepare a
   manifest, backup and tested rollback. Use the already-approved brief maintenance
   window only after gates pass; pause workers as well as web writes, then apply the
   tested release and perform bounded live checks. Reopen only after success.
7. **Remove obsolete dependencies and close the register.** Audit supported runtime,
   installer, worker, operator, build and embedded paths. Remove MDB2/MDB2_Schema and
   driver installation requirements and vendor files only after no supported consumer
   needs them. Preserve necessary attribution and explicitly managed historical test
   oracles. Run from a clean image without those packages to prove closure. Review
   remaining PEAR families individually: MDB2 removal is not blanket deletion authority.

## Required proof matrix

| Layer | Minimum acceptance evidence |
| --- | --- |
| Real database contracts | SELECT/SHOW and writes; associative keys/case; empty row/set; null versus empty/zero/false; integer/string/decimal representation; Unicode and quotes/backslashes; limit/offset; affected rows including no-op and failed compare-and-set; application IDs versus puid; native prepared parameters and repeated placeholder handling. |
| Failure and atomicity | Missing/duplicate rows, constraint errors, failed connection/begin/commit, rollback after partial writes, nested ownership, lock timeout/deadlock and safe retry policy. No secret SQL/credentials/content in browser errors. No automatic retries of non-idempotent external effects. |
| Concurrent invariants | Two real connections competing for token use/rotation, MFA recovery, registration cleanup, translation locks, generation claims, payment/event idempotency and capacity allocation where supported. |
| Encoding | Preserve existing table collations and translation bytes. Test utf8mb4 stores separately. Do not combine the adapter migration with a bulk charset or content migration. |
| Schema lifecycle | Fresh install; normal upgrade and repeated upgrade; expected indexes/constraints/defaults; failed DDL surfaced correctly; backup restoration rehearsed. |
| Local browser | Anonymous home/login and access gates; authenticated home/profile/preferences; logout/relogin; permissions/admin denial; MFA and remembered login with test accounts; course/content reads and reversible edits; Knowledge Maps, Kanban, notes; quiz/question workflows with paid calls stubbed; language lookup; catalogue/admin pages. Check console, PHP logs, server responses and persistence after reload. |
| Production rehearsal | Exact image plus release files; cloned data kept private; mail/payment/AI/webhooks disabled; protected-table and language fingerprints; schema/configuration comparison; workers/CLI checks. Record any intentionally changing operational rows separately. |
| Live acceptance | Bounded read/login/browser checks, connection driver verification, logs and critical service health. Avoid destructive probes and external side effects. Restore service with the previous code/image/config on failure where schema compatibility permits. |

Existing unit/smoke suites remain valuable, but mocks or SQLite do not establish
MariaDB transaction, affected-row, collation or DDL compatibility. Record the exact
commands, environment and results at implementation time; this plan does not claim
those future tests have passed.

## Rollback and completion

Prefer an adapter-only release with unchanged application schema. Keep the previous
image, release directory and configuration immediately available. If a required
schema change emerges, split it into a separately rehearsed compatible change or
write a specific recovery procedure before release. Back up immediately before the
window and verify restoration on a clone. After reopening, never restore an old
backup over new user work without a reconciliation decision.

Completion means all supported entry points run on PDO, the exact production image
passes the matrix, meaningful changes and evidence are committed/pushed, and the
PEAR register explicitly records what was removed or intentionally retained. No
unexplained candidate, undeclared deployed change or untested installer should be
hidden behind a successful home-page check.

## Effort and next bounded task

Allow roughly **4–7 focused working days for the KengaLearn-supported path**, including
real database tests, browser verification, rehearsal and cleanup. This is a planning
range, not elapsed-time commitment. Broad historical module/driver rehabilitation
is additional work; schema irregularities or concurrency failures can extend it.
The earlier 3–5 day estimate was optimistic before identifying the missing MySQL
PDO driver and separate security/schema paths.

The next bounded task is step 1: prepare the PDO-capable image and freeze dbTable
contracts on disposable MariaDB fixtures. It produces a concrete go/no-go result
without changing the live site and does not require continuous user attention.
