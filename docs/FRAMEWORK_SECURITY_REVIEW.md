# Framework security and legacy-modernisation review

Status: discovery only. This document does not authorise deletion, dependency
upgrades, or changes to deployed configuration.

## Scope and order

The review starts in `app/classes/core` and `app/core_modules` before the
application-module repositories converge onto this framework revision.

1. Establish the production/runtime inventory: PHP image and version, enabled
   extensions, web-server configuration, PHP INI, writable paths, sessions,
   secret sources, scheduled jobs, and exposed entry points.
2. Trace authentication, session restoration, authorisation and CSRF from the
   framework entry point through `security` services. Exercise anonymous,
   revoked, ordinary-user and administrator paths.
3. Review framework-wide input/output and persistence boundaries: routing,
   redirects, file access, uploads, rich-text sanitising, database quoting and
   prepared statements.
4. Review bundled third-party code and configuration. Identify active callers
   before removing or replacing any asset.
5. Triage findings as Critical, High, Medium or Modernisation, with a minimal
   reproducible test and a safe remediation plan for each.

## Initial evidence

* `app/lib/pear` contains approximately 1,360 PHP files. It includes multiple
  historical database drivers, PEAR installer tooling, LiveUser, MDB/MDB2,
  Mail, XML/RPC, HTML QuickForm, Translation2, and other packages.
* The core engine still configures `lib/pear` on the include path and loads
  `LiveUser.php`, `LiveUser/Admin.php`, and MDB2 when the configured database
  abstraction requests it. `dbTable` and `dbTableManager` retain broad MDB2
  compatibility behaviour.
* Framework callers outside the vendored directory still directly use PEAR
  components for the legacy mail module, configuration, language/translation,
  BBCode/XML-RPC helpers and selected utilities. A static match is evidence of
  a possible dependency, not proof that a package is reachable in production.
* The maintained security module already contains native-auth work, while
  `LiveUser` compatibility and shadow classes remain. This boundary needs a
  dedicated reachability review before any package removal.
* Potentially dangerous primitives such as `unserialize`, dynamic includes,
  and `eval` occur in core or bundled code. Each requires a data-origin and
  reachability assessment; no finding should be inferred solely from a text
  match.

## PEAR replacement register

| Component | Known framework use | Direction | Removal condition |
| --- | --- | --- | --- |
| MDB2 / MDB2_Schema | Core engine, `dbTable`, schema/table management | Keep a compatibility boundary while incrementally moving active callers to the canonical PDO path | No runtime uses of MDB2 mode, schema tooling migrated, and representative database regression tests pass |
| LiveUser | Engine bootstrap and legacy security compatibility | Retire behind the native-auth services | Production auth/session paths and every compatibility caller are proven independent |
| PEAR base / PEAR_Error | Core error handler and MDB2 error checks | Replace with native exceptions/results as MDB2 and LiveUser are retired | No active class extends or checks PEAR types |
| Mail / Mail_mime / Net_SMTP | Legacy `mail` module | Keep isolated; use the Communications service for new work | All live mail sending uses Communications and migration/rollback evidence exists |
| Config | Core configuration compatibility | Replace only after current configuration formats and installation flows are mapped | Installer and all deployed configuration readers use the replacement |
| I18Nv2 / Translation2 | Language services | Treat as a separate, high-regression migration | Locale negotiation and every language fallback has compatibility coverage |
| HTML QuickForm / XML-RPC / BBCodeParser | Older UI, filter and utility paths | First establish whether these routes are enabled or reachable | No registered module, endpoint, or runtime caller depends on them |
| PEAR installer, PHPUnit2, old database-driver trees | Build/test/installation baggage and unused drivers are plausible | Quarantine and verify reachability before removal | Build, installer, and supported runtime matrix no longer reference them |

## Cleanup rules

* Do not delete directories because they look old, are labelled legacy, or are
  absent from a simple static search.
* For every candidate, record direct callers, runtime/module registration,
  production reachability, test coverage, replacement, rollback plan and a
  removal commit.
* Remove obsolete drivers and installer assets in small, independently
  deployable changes; never combine them with behaviour changes or a security
  patch.
* Keep a software bill of materials with bundled component version, source,
  licence, active/inactive classification, and known replacement plan.

## First review targets

1. `app/classes/core/engine_class_inc.php`
2. `app/classes/core/dbtable_class_inc.php` and `dbtablemanager_class_inc.php`
3. `app/classes/core/controller_class_inc.php`
4. `app/core_modules/security/`
5. `app/core_modules/files/`, `app/core_modules/filters/`, and public gateway
   entry points
6. The active production PHP/container and web-server configuration

## Deliverables

* A reachability map and SBOM for bundled legacy dependencies.
* A verified security finding register, including severity, evidence, affected
  deployments, remediation owner, tests and rollback notes.
* A staged PEAR retirement plan that starts with unused database drivers and
  installer/test-only packages, not core MDB2/auth compatibility.
