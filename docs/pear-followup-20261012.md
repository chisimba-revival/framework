# Remaining PEAR cleanup — 12 October 2026

This follow-up removes source and local runtime dependencies. It has not been
deployed to production. The previous six-site rollout and PDO conversions remain
as recorded in `pear-rollout-20261012.md` and `database-migration/`.

## Removed after caller tracing

- PEAR RDF (`RDF.php`, `RDF/`): only application consumer is
  `modules/foaf__PAUSED/classes/foafparser_class_inc.php`. RDFGen uses its own
  separate bundled RAP API, not this PEAR package.
- PEAR MDB (`MDB.php`, `MDB/`): predecessor of MDB2, referenced by the removed
  RDF store. Not the optional MDB2 database backend.
- PEAR Validate (`Validate.php`): remaining caller is the paused FOAF parser.
  MDB2's separately namespaced schema validator remains intact.
- Both `Auth/OpenID/MDB2Store.php` copies (app/lib and security/classes): no
  application instantiation. LoginInterface explicitly selects FileStore in both
  initiation and completion paths. Other OpenID code remains unchanged.

The five PEAR paths are added to the shared retired-path manifest; both OpenID
adapter paths are checked by the retirement guard. The curated dependency builder
already omits these packages. There is no module, schema, user-data or login-policy
change. FOAF must be modernised before reactivation.

## Verification

- Full PDO and MDB2 suites passed against disposable databases, including
  authentication, permissions, rollback, concurrent operations and Kanban ordering.
- Native engine composition passed with PEAR/MDB2 absent from loaded classes;
  native installer created 107 registered definitions and preserved repeat updates.
- Retired-path guards and whitespace checks passed.
- Removed the same libraries from the local assembled runtime, retaining a
  temporary container backup at `/tmp/pear-followup-20261012`.
- Real local PDO engine, translations, country data, catalogue and MFA repository
  composed successfully with strict PHP diagnostics. Authenticated browser checks
  of Site Administration and Module Catalogue passed; console warnings/errors empty.
- Local test evidence: workspace `work/pear-followup-20261012/`.

## Remaining dependency groups and next work

1. PEAR DB: old installer repair paths in `installwizard.inc` and
   `steps/secondarydetails.inc` call `DB::parseDSN`. Migrate to shared native parsing
   only after comparing protocol/socket/default-field semantics; the current
   shared parser is not an exact drop-in replacement. Test repair and fresh install,
   then remove DB and its drivers. No production runtime database uses this library.
2. XML-RPC: core Packages/API RPC classes and Filters, plus CMS/CMSAdmin,
   SimpleGal and the historical IM client retain the original XML_RPC API.
   Trace actual dispatch and site usage before retiring endpoints or replacing
   protocol support; pausing Blog did not eliminate every RPC caller.
3. Optional MDB2: all six managed sites use PDO, but framework fallback and its
   regression harness still support MDB2. Its schema parser retains XML_Parser,
   Serializer/Unserializer and XML_Util. Retiring this backend requires an explicit
   unsupported-backend diagnostic, updated installer/runtime dependency checks,
   and a PDO-only test baseline before deleting the libraries.
4. PEAR.php: retain until the above compatibility/error consumers are eliminated.

Do not delete the entire PEAR directory yet. Include the new deletion manifest and
both explicit OpenID adapter deletions in a future rehearsed production release;
copying only changed files will leave the obsolete libraries behind.
