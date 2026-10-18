# Framework security and legacy-modernisation review

Status: source remediation and focused test pass complete, 29 September 2026.
The completed changes are not yet deployed to production.

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
* The core engine still configures `lib/pear` on the include path and can load
  MDB2 when the configured database abstraction requests it. `LiveUser` and
  unused LDAP adapters have been removed; `dbTable` and `dbTableManager`
  retain broad MDB2 compatibility behaviour.
* Framework callers outside the vendored directory still directly use PEAR
  components for the legacy mail module, configuration, language/translation,
  BBCode/XML-RPC helpers and selected utilities. A static match is evidence of
  a possible dependency, not proof that a package is reachable in production.
* Native authentication is the sole supported identity path. Its boot,
  canonical-session, cookie-policy and logout contracts pass without
  LiveUser being present.
* Potentially dangerous primitives such as `unserialize`, dynamic includes,
  and `eval` occur in core or bundled code. Each requires a data-origin and
  reachability assessment; no finding should be inferred solely from a text
  match.
* Active Dynamic Mirroring, a historical SQL-write replication system, has no
  current KengaLearn configuration. Its configuration, SQL logging, ADM
  XML-RPC service and conversion scripts are retired in source with focused
  local regression coverage. Generic XML-RPC remains separately tracked.

## PEAR replacement register

| Component | Known framework use | Direction | Removal condition |
| --- | --- | --- | --- |
| MDB2 / MDB2_Schema | Core engine, `dbTable`, schema/table management | Keep a compatibility boundary while incrementally moving active callers to the canonical PDO path | No runtime uses of MDB2 mode, schema tooling migrated, and representative database regression tests pass |
| LiveUser | Retired from engine bootstrap and filesystem | Removed behind native-auth services | Complete in source; production login/logout verification remains before release |
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

## Pause-point handoff

### 30 September: controller authorisation verification

Follow-up: the local `admin` credentials were valid. The smoke script omitted
the current anti-abuse form evidence and minimum submission delay. Corrected
those, curl POST redirect handling, malformed action query strings and the
logout action assertion. The complete administrator HTTP login/logout smoke
test now passes, including toolbar, permission editor and Knowledge Map routes.
No password or application security control was changed.

With explicit user approval, `controller_authorisation_runtime_test.php` created
a disposable local user and two namespaced role groups through canonical
services. Real persisted outsider/student/lecturer/revoked-role policies and
administrator policies passed. The exact user, permission identity and groups
were removed through guarded service calls; its isolated CLI session was ended.
This test sets canonical session identity in CLI; it does not claim end-to-end
HTTP journeys for each temporary role or test course admission/rendering.
Permission-editor initialisation emitted existing dynamic-property deprecations
in its controller and decision-table classes. Record these for separate cleanup.
F-005 remains undeployed. The browser checks below now satisfy the targeted
local role-journey gate; a narrow production release and post-release checks
remain separate work.

#### Completed local browser journeys — 30 September

Using Chrome against `https://chisimba.test:8445/ch`, with ordinary form logins,
four disposable users, two disposable Public courses and one disposable map:

| Actor / journey | Observed result |
| --- | --- |
| Anonymous visitor | Public course home opens; membership management requires sign-in. |
| Student | Public course home opens; membership and permission-rule screens deny access. |
| Signed-in outsider | Public course home opens; both management screens deny access. |
| Lecturer from another course | Denied management of the first course; allowed membership screen in their own course. |
| Same-course lecturer | Membership screen opens; adding the disposable outsider as a Student succeeds; permission-rule screen denies access. |
| Lecturer revoked while signed in | An already-rendered form submission is denied for `addusers`; reopening membership management is also denied. Storage confirms the attempted Guest grant did not occur and the earlier Student grant remains. |
| Administrator | Course home, membership management and permission-rule editor open; normal login/logout succeeds. |
| Knowledge Map owner | Creates disposable map, adds/edits a node, observes autosave, reloads and verifies persistence. Public-link creation and revocation update in place. Copy button reports success and clipboard exactly matches the URL. |
| Anonymous map viewer | Public map opens read-only without editing, sharing or embed controls. After revocation the same token reports the map unavailable (also verified in an administrator session). |

These are targeted regression journeys for the permission-dispatch release,
not exhaustive certification of every action in every module. Application code
was not changed during this browser pass and nothing was deployed. Existing
maps and course memberships were not edited. Fixtures were removed and their
absence verified; the browser was restored to the local administrator account.

Follow-up cleanup leads from fixture setup/teardown:

* `createContext()` retains historical `showcomment='Y'`/empty-alert defaults
  against numeric local columns. The first fixture calls returned success but
  no course rows. Supplying the normal numeric form values created the fixtures.
  Investigate defaults and database error propagation separately.
* The legacy course deletion helper left fixture role groups behind. Exact
  remaining groups were removed using canonical GroupService, and their absence
  verified. Investigate complete course/group lifecycle cleanup separately.
* Previously recorded legacy dynamic-property warnings still occur in course,
  search and permission-editor setup; this pass did not suppress or change them.

The earlier login blocker described below is resolved by the test-harness fixes.

Added `tests/core/controller_authorisation_behaviour_test.php`, executing the
actual dispatcher and controller policies against isolated role fixtures.
It covers outsider/student/revoked denial, same-course lecturer and administrator
access, other-course lecturer denial, administrator-only permission editing,
unknown membership actions, and method/context/CSRF mutation guards. Public
dispatch remains accessible. This is not a persisted-role or HTTP journey test.
The existing membership and administrator policy contracts also pass.

Read-only inspection confirms KengaLearn still has the bypassed generic
dispatcher and the old `contextpermissions` fallback: F-005 is not deployed.
The local HTTP smoke test reached the native login form and rejected incorrect
credentials, but `admin / a` did not authenticate. Subsequent authenticated
checks therefore could not establish a session; they are not evidence of
individual route regressions. No account was reset and no production change
was made. A working local test login (or an explicitly approved disposable test
account) is needed to complete the real role/session journey gate before release.

### 30 September: legacy endpoint retirement

KengaLearn now runs
`/srv/kengalearn/releases/release-legacy-endpoints-20260930-102622/ch`.
This release overlays only the `api` and `packages` controllers and their
retirement templates onto the previous working release. Default requests and
GET/POST `serveapi` requests return HTTP 410 with the retirement response.
The web container is healthy, home-page readiness passed, and no PHP fatal,
warning or parse errors appeared in the container logs checked after release.
The complete Knowledge Map module tree matches the previous release.

Tests: `tests/core/legacy_endpoint_retirement_test.php` executes both real
controllers against inert framework stubs and rejects legacy actions without
loading services; the ADM retirement contract and deployed PHP lint pass.
No schema, user data or shared authentication code changed in this release.
Rollback target:
`/srv/kengalearn/releases/release-knowmap-ajax-copy-20260930-095359/ch`;
switch the active release link back and recreate only the web service.
Backing XML/RPC classes/libraries are not yet deleted. Other sites are not
covered by this release. The controller-authorisation role matrix remains the
next deployment gate for F-005.

Completed source commits: `bc31bb040` (protected-controller authorisation),
`e9a8a4f33` (session cookies), `41f742c80` and `e809cafb3` (LiveUser/LDAP
retirement), `8f9423dbc` (authorisation dispatcher correction), and
`63a5ed4ba` (Memcache runtime retirement). The focused release suite passed:
native-auth session and logout contracts, authorisation contracts, CPD UI
contract, Memcache retirement, runtime-warning foundations and the PHP 8.5
production-security baseline.

Before production release, execute a real KengaLearn anonymous, learner,
lecturer and administrator journey; verify login, remembered login, logout,
protected membership and permission mutations, cookies and response headers.
Then deploy the already-prepared PHP 8.5.11/session/error/header baseline and
verify the same checks against production. Do not combine that release with
Knowledge Map work.
