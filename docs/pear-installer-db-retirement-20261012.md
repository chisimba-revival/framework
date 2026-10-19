# Installer PEAR DB retirement — 12 October 2026

Removed `app/lib/pear/DB.php` and `DB/` after migrating both installer repair
callers to `ChisimbaDatabaseDsn::forInstaller`. Runtime `parse()` is unchanged.
The repair location helper preserves explicit ports and Unix socket paths when
reconstructing a DSN. Parsed credentials remain decoded once; option strings do
not become part of the database name or override credentials. Missing fields
are strings, avoiding PHP 8 null/false string-function warnings.

The old bundled DB parser's `is_countable()` guard returned early for string DSNs.
Tests assert intended settings and native PDO reconstruction, rather than copying
that defect. No login policy, schema or database data changes.

Caller search across framework/modules found no remaining application consumer
of PEAR DB. Historical vendor ADOdb test/demo files in RDFGen/paused FOAF still
mention it; the ADOdb compatibility bridge defines its own DB class and is not an
application call to the removed package. Those vendor demos are not supported
Chisimba entry points. Optional MDB2 and XML-RPC remain supported for now.

Validation: native repair parsing tests, PDO destination reconstruction, syntax,
retired-path guard, installer requirements (driver present/absent), catalogue seed
repeatability, 13 configuration/handoff checks, full PDO/MDB2 disposable database
suites, and native installation of 107 schema definitions all passed. Local native
configuration/translation/catalogue/authentication smoke passed with DB absent.
A disposable read-only-source browser server rendered Welcome and the licence
page without PHP diagnostics; no complete browser installation was repeated.
The server was removed afterward. No live database was used for installer tests.

Source and local assembled runtime updated; production unchanged. Local removed
library backup: container `/tmp/pear-installer-20261012`. Evidence lives in workspace
`work/pear-installer-20261012/`. Deployment must apply the shared retired-path
manifest, not merely copy modified files. Remaining work is XML-RPC caller
retirement/migration and retirement of the optional MDB2/XML dependency chain.
