# Schema migration requirements — local source review, 11 October 2026

This document records the pre-implementation assessment. Its implemented scope,
test results and remaining unsupported legacy operations are now recorded in
[local acceptance](local-acceptance-20261011.md). The local site has switched to
PDO; the requirements below are retained as the review checklist.

## Current paths

- `modulecatalogue/modulesadmin::makeTable()` includes module SQL-definition
  files, creates the table, then creates declared indexes. Existing tables are
  skipped via tableinfo; repeated registration must preserve their contents.
- `dbTableManager::createTable()` adds an auto-increment `puid` definition with
  integer length 50, chooses InnoDB and defaults to utf8/utf8_unicode_ci, and then
  calls `createPK()` for the application's `id`. Capture actual MariaDB output
  before reproducing assumptions about which key is PRIMARY or unique.
- `createTableIndex()` currently generates index names except in its explicit
  truncation flag path. Compare effective column/uniqueness/order semantics;
  do not infer index meaning from generated names.
- The manager also handles alter-table changes, index/constraint inspection,
  sequences, database selection, XML schema parsing/reversal and dumps.
  `engine::getDbManagementObj()` and these manager calls remain MDB2-dependent.
- `app/installer/dbhandlers/dbhandlermanager.inc` has a separate implementation
  of table/index creation and schema operations. Updating the runtime manager
  alone will not make fresh installation PDO-ready.
- Language administration now uses the narrow ChisimbaDatabaseSchema text-table
  service. Extend shared schema machinery where appropriate; do not implement
  MDB2's magic mg*/rv* method dispatch on the native PDO connection.

## Required implementation and acceptance

1. Capture real MDB2 schema results for representative tracked definitions:
   data types, lengths, null/default values, charset/collation, auto-increment,
   application IDs, unique/non-unique/composite indexes and supported alterations.
2. Map actual runtime/catalogue/installer callers to explicit shared schema
   operations. Classify unused XML dump/sequence/operator paths separately;
   no silent success stubs or untested compatibility emulation.
3. Run equivalent definitions under PDO and compare information_schema output
   and SHOW CREATE TABLE. Preserve existing encodings; utf8mb4 conversion is a
   separate migration, not an implicit database-adapter change.
4. Exercise normal catalogue registration/patches twice on disposable data,
   failed DDL and restore rehearsal; then fresh installation. DDL can commit
   implicitly, so a PHP transaction does not make an entire upgrade reversible.
5. Reconcile local registration gaps during that rehearsal before applying any
   scoped installed update. Local toolbar is registered at 1.806; navigation
   profile/hint strings exist in source but the inspected three IDs are absent
   from tbl_en. Browser editor reads work, but those labels display as missing.

## Inventory refresh

The audit now scans tracked and non-ignored untracked PHP/INC/SQL files and records
working-tree changes alongside HEAD. This is necessary while migration files
remain deliberately uncommitted. The original committed audit remains historical.
The refreshed local registry has 95 modules and still reports MDB2_Driver_mysqli.
Current output is in workspace work/pdo-local-20261011/current-audit, including
local-candidates.csv. Signals still require object-type/reachability review:
repository calls to their dbTable field are not direct driver calls merely
because a regex matches them. Do not interpret candidate totals as migration
completion percentages.
