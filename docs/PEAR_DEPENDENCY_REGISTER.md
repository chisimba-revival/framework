# PEAR dependency register

Status: native events, tag clouds and configuration were released to KengaLearn
on 10 October 2026; native translation/locale lookup followed the same day.
Translation release: `b97ac37d4` plus `3e1aabebd` over the earlier framework
`6e101d2ee` / modules `9cdde04de` baseline. Evidence and rollback details are in
`tests/configuration/README.md` and `tests/translation/README.md`. MDB2-to-PDO,
the language-administration interface and final vendor cleanup remain separate.
See the dated assessment below for corrected scope and replacement sequencing. A direct load proves that
the framework can use a component; it does not prove that the corresponding
module is installed, enabled or reached in KengaLearn production. Do not
remove a package from `app/lib/pear` until runtime registration and supported
deployment checks have been recorded.


## Required final cleanup gate — recorded 10 October 2026

This PEAR programme is not finished when callers merely stop using a package.
After configuration, translation and database replacements pass their release
checks, remove the retired packages from source, dependency packs and assembled
release outputs. Remove obsolete bootstrap includes, compatibility adapters,
repair scripts and vendor-only tests; retain native behavioural regression tests.
Audit installer, CLI/jobs, external extensions and each supported deployment;
rebuild from the cleaned manifests and prove removed classes are not loaded.
Record licences, exact removals and a tested rollback. Do not delete still-used
MDB2/Translation2 components as part of the current configuration release.

Database work is queued after the completed PEAR release. Initial inspection
found existing PDO branches are not a drop-in switch: connection construction,
result shape, direct MDB2 consumers, native-auth adapter and schema/installer
paths need separate compatibility coverage. No database-layer setting or schema
has been changed. Keep the deployment and database migration as separate commits.

## Bootstrap and persistence boundary

| Component | Direct framework caller | Present role | Replacement direction | Removal gate |
| --- | --- | --- | --- | --- |
| LiveUser / LiveUser_Admin | None: bootstrap and package files removed | Retired; native-auth is the supported identity boundary | Complete in source | Focused native-auth/session/logout contracts pass; production journey verification is pending release |
| MDB2 / MDB2_Schema | `engine_class_inc.php`, `dbtable`, `dbtablemanager` | Configurable database abstraction and schema compatibility | Establish PDO as the canonical path; migrate API-by-API rather than replacing calls mechanically | No supported setting selects `MDB2`; schema install/upgrade tools and regression suite use PDO |
| PEAR / PEAR_Error | Engine error callback, MDB2 error checks and historical base classes | Error representation coupled to MDB2 and LiveUser | Use native exceptions/results at adapter boundaries | MDB2/LiveUser removed and no active code extends or type-checks PEAR classes |
| Config | No identified active first-party loader after native migration | Vendor retained for legacy fixtures/external-extension audit | Native XML/INI documents and checked writers now serve all identified callers | Deployment/extension audit and full database installation before vendor removal |

## Module-scoped direct loaders

| Component | Direct caller(s) | Exposure to establish | Direction |
| --- | --- | --- | --- |
| Mail / Mail_mime | `core_modules/mail/classes/mailer_class_inc.php` | Whether legacy `mail` remains registered and sends production messages | Keep isolated; route new sending through Communications. Retire only after delivery, attachment and bounce/rollback tests |
| Translation2 / Translation2_Admin / I18Nv2 | No identified active first-party loader after Language 1.613 | Native runtime released to KengaLearn; external-extension/installer audits outstanding | Retain vendors until release/cleanup gates pass; see translation test README |
| XML/RPC | `core_modules/packages/classes/rpcserver_class_inc.php`; `core_modules/api/classes/xmlrpcapi_class_inc.php`; filter helpers | **F-006 update, 30 September:** KengaLearn's `api` and `packages` routes return HTTP 410 without loading services. ADM is retired. Backing classes and shared libraries remain; other sites require separate verification. | Do not restore legacy endpoints. Inventory internal/filter consumers before deleting XML/RPC libraries. Future integrations should use explicitly authenticated and authorised HTTP/JSON services. |
| HTML_BBCodeParser | `core_modules/utilities/classes/bbcodeparser_class_inc.php` | Which rich-text inputs select this parser and the output sanitisation chain | Retire after content migration and sanitised rendering tests |
| Archive_Tar | `core_modules/modulecatalogue/controller.php` | Module package upload/install feature reachability | Replace with a maintained archive implementation only after archive traversal and package signature policy are defined |

## Candidate baggage, not removal candidates yet

The vendored tree also contains historical package-manager code, PHPUnit2,
multiple old database drivers and many packages without an identified direct
caller. These are *candidates for investigation*, not unused code. Before
quarantining any one of them, record:

1. direct and indirect loads, including include-path resolution;
2. module registration and production reachability;
3. supported installer, CLI and scheduled-job paths;
4. package version, upstream source, licence and known security advisory
   status;
5. a replacement or no-op proof, tests, deployment plan and rollback commit.

## Historical September sequencing (superseded by the October assessment)

1. Finish native-auth versus LiveUser reachability mapping without changing
   current production bootstrapping.
2. Complete the production module catalogue inventory for legacy Mail and
   the now-contained XML/RPC/package routes across the remaining sites.
3. Make PDO compatibility testable in a dedicated branch; do not flip the
   production database abstraction as part of a security hotfix.
4. After that evidence, isolate the smallest inactive database-driver or
   test-only package set in a reversible removal change.


## Assessment — 10 October 2026

This is a good time to reduce PEAR use in bounded steps. Start with application
behaviour that Chisimba can own clearly: event dispatch and tag-cloud rendering.
Keep database, localisation and configuration migrations separate. Native code
means small Chisimba services using PHP facilities, not rebuilding every PEAR API.
The assessment itself changed only documentation. The local event-dispatch
implementation completed afterwards is recorded below.

### Evidence and limits

Inspected framework `413821164` and modules `5112a71af`, both on
`release/consolidation-20260930`, with existing uncommitted work preserved.
The dependency builder is on dev-environment `feature/scoped-file-storage` and
also has existing changes. Host CLI is PHP 8.5.4. The running local web container
is `chisimba-php85-web`; its mounts expose framework classes/core modules/skins
and the modules checkout, but `lib/pear` comes from the assembled
`dev-environment/runtime/php85-ch` tree. Source and runtime libraries therefore
need separate verification.

Source scans covered PHP loaders, PEAR class references and wrapper consumers
in framework engine/core modules/installer and the application modules tree.
Resource/vendor trees were excluded from the primary caller scan and inspected
selectively. Comments, examples and disabled paths are not proof of live use.
No production module registration query, authenticated route trace, complete
transitive dependency graph or security-advisory audit was performed. Package
presence and source reachability are established here; per-site execution is not.

The source PEAR tree occupies approximately 18 MiB; the curated pack is about
4.7 MiB. These filesystem sizes are not evidence that the difference is removable.

### Corrected functional inventory

Paths below are relative to `app/` unless prefixed with `modules/` (the sibling
application repository) or `dev-environment/` (the sibling build repository).

| Function / dependency | Evidence and actual scope | Recommendation |
| --- | --- | --- |
| Event dispatch — Event_Dispatcher, Event_Notification | `classes/core/engine_class_inc.php` loads it unconditionally. Base objects/controllers/dbTable expose it. Context plus announcements, assignment, discussion, contextcontent, practicals, tutorials, glossary, mcqtests, worksheet and other modules register observers/post events. `modules/activitystreamer` consumes notification name/info. | Strong first native-service candidate. Preserve observed delivery semantics and migrate bootstrap plus all supported callers together. |
| Configuration — Config, Config_Container | `core_modules/config/classes/altconfig_class_inc.php` constructs Config and reads `config.xml`; also catalogueconfig, userparamsadmin and the INI helper. This is a bootstrap dependency, not just INI tooling. | Native reader/writer is worthwhile after fixture-based format contracts. Keep XML/INI formats and public configuration getters stable. |
| Database — MDB2, MDB2_Schema, PEAR errors | Engine, dbTable and dbTableManager; modules also directly use MDB2 constants/result methods. Native authentication still has an MDB2 database adapter. | High-value, high-risk separate project. PDO branches exist but are not proof that changing the setting is sufficient. Include schema installation/upgrades and direct consumers. |
| Language — Translation2/Admin, I18Nv2 | `core_modules/language/classes/language_class_inc.php` initialises languageconfig, translation/admin objects and I18Nv2 locale. languageconfig explicitly selects the MDB2 driver. | Replace behind the language service, retaining existing tables, fallback behaviour, module registration, cache behaviour and systext substitutions. This affects ordinary rendering, not only locale administration. |
| Mail — Mail, Mail_mime and transport dependencies | Legacy mailer supports sending and attachments; callers include discussion, practicals, filemanager, forms, userregistration and legacy user administration. | Move callers into existing Communications. Its current worker supports null/SendGrid; inspected SendGrid payload has text/HTML but no attachments. Add required transport/attachment contracts before migrating those callers. Do not hand-write SMTP/MIME. |
| Tag clouds — HTML_TagCloud | Utilities wrapper used by tagging, filemanager, catalogue, FAQ, old blog and digitallibrary. SimpleBlog's tag block already uses its publishing renderer. | Strong small native-renderer candidate. Keep classification/permissions with existing owners and put presentation in shared UI/skin. |
| XML serialisation — XML_Serializer/Unserializer | Utilities xmlserial used by filemanager media analysis, triplestore and ETD metadata. | Use PHP XML facilities behind a narrow contract only after fixtures establish current array/XML shapes; a generic SimpleXML conversion is not equivalent. |
| BBCode — HTML_BBCodeParser | Utilities wrapper, washout, old blog and catalogue descriptions. | Establish supported syntax and stored-content fixtures first. Retain the output sanitisation boundary; do not replace nested markup parsing with a few regexes. |
| XML-RPC — XML_RPC | API/packages backing classes, filters, old blog, CMS, simplegal and IM source paths. Earlier route retirement did not remove all consumers. | Retire unsupported integrations per caller. New integrations use explicit HTTP/JSON contracts; do not build a native clone of XML-RPC merely to remove PEAR. |
| Archive install — Archive_Tar | Module Catalogue package upload/extraction path. | Use a maintained/native archive facility only with traversal, links, size limits and failure/rollback tests. No custom TAR parser. |
| Graphs — Image_Graph | `core_modules/utilities/classes/graph_class_inc.php` directly requires it. | Establish supported graph consumers before selecting a renderer or retirement. Not proven unused. |
| Legacy XML tree/beautifier — XML_Tree, XML_Beautifier | Tree menu and FOAF reference these; neither `XML/Tree.php` nor `XML/Beautifier.php` exists in the inspected source or local assembled PEAR tree. | Record as unresolved legacy paths, not dependencies safe to delete. Retire consumers or migrate required XML production to PHP facilities. |
| Other module dependencies | FOAF loads Validate; old blog loads Net/DNSBL; maillist loads Net/POP3 and Mail/mimeDecode. | Verify module support/registration first. Prefer retiring obsolete functionality or a maintained implementation over recreating network protocols. |
| Calendar | In curated manifest; no `Calendar/`, `Calendar.php` or `Calendar_` match in the primary non-vendor PHP caller scan. calendarbase has its own generator. | Candidate for wider dependency/reachability investigation, not deletion based on this scan. Reuse timeanddate-service for new date behaviour. |
| LiveUser | Absent from source PEAR tree; engine getLU() returns null. `LiveUser.php` is still present in assembled local runtime. | Source retirement is already done. Track removal from release/assembly outputs separately, after supported runtime checks. |

### Build reproducibility needs attention first

`dev-environment/dependencies/pear-packages.tsv` contains eight entries, including
XML_Serializer, which the human-readable build register omits. It does not list
Event_Dispatcher, Config, Translation2, I18Nv2, Mail or HTML_TagCloud despite the
source dependencies above. It is a partial compatibility-pack manifest, not a
complete bill of materials for Chisimba.

The inspected `build-chisimba-pear-runtime.sh` replaces selected source-tree
subdirectories and overlays the pack. In particular it removes the whole XML
subdirectory and installs the manifest's XML subset. Existing references to
XML/Tree and XML/Beautifier are outside that subset and currently unresolved in
both inspected trees. Other historical top-level families remain outside the
manifest. Do not run this builder as a generic cleanup operation.

Before package deletion, capture a complete supported-runtime bill of materials
with origin, licence, pinned version/hash and transitive dependencies, then make
assembly checks fail on required missing files. Include the resolved include
path: engine getPearResource() tries an include before its bundled fallback, so
an installed system package could mask an incomplete bundled tree.

### Recommended work packages and acceptance gates

1. **Reproducible inventory.** Combine source caller evidence, per-site registered
   modules and included-file traces for bootstrap, catalogue, language admin,
   representative content and CLI jobs. Reconcile source/assembled libraries and
   manifest coverage. Keep private configuration and credentials out of reports.
2. **Native event dispatch.** Define a Chisimba event/notification service and test
   observer registration, duplicate registration, ordering, sender/name/payload,
   removal/cancellation where supported, and exception behaviour against the
   existing dispatcher. Check the actual activitystreamer database/feed outcomes.
   A generic callback loop alone is not a validated replacement. Remove the PEAR
   bootstrap load only when all supported consumers pass.
3. **Native tag rendering.** Preserve buildCloud/addElement and the historically
   misspelt biuldAll caller contract during migration, or update all callers in
   the same change. Test empty/equal/zero weights, Unicode, escaping, unsafe URLs,
   stable ordering and repeated use; verify shared CSS, narrow layout and keyboard
   links. Check use of the public tags property before removing the adapter.
4. **Configuration and language, separately.** Capture real format/schema shapes
   with synthetic fixtures. Cover configuration round-trips and failed writes;
   language defaults, missing/empty entries, locale switches, placeholders and
   registration updates. Preserve stored data and support rollback without a
   simultaneous database-format conversion.
5. **Database migration.** Characterise quoting, nulls, transactions, insert IDs,
   fetch modes, affected-row semantics, pagination, errors and schema management.
   Migrate direct MDB2 consumers and the native-auth adapter; test both fresh
   installation and upgrade. Remove PEAR error handling only after remaining
   libraries stop requiring it.
6. **Mail/content/integration retirement.** Move complete workflows through existing
   services, then remove their unused libraries after site and job verification.
   Use null/fixture transports for tests; no real mail is needed for this audit.

Each implementation should be a separate reversible change with focused tests
and an assembly/release manifest update. Package-manager tooling, PHPUnit2 and
unused drivers remain candidates until installer/CLI/transitive checks establish
that they are outside the supported product.

PHP's [PDO documentation](https://www.php.net/manual/en/book.pdo.php) describes a
data-access abstraction, not automatic SQL/schema portability; that distinction
is why the database gate includes dbTableManager. PHP's
[XMLWriter documentation](https://www.php.net/manual/en/book.xmlwriter.php)
provides a native XML output boundary, but Chisimba must still specify its data
mapping and preserve existing formats.


### First implementation — native event dispatch, 10 October 2026

The engine now creates `ChisimbaEventDispatcher` from
`app/classes/core/nativeeventdispatcher.php`; notifications use
`nativeeventnotification.php`. Framework objects keep receiving the same engine
property, so existing module observers need no source changes. Local runtime
bootstrap, translation and constant database-query checks confirm that neither
PEAR Event class/file is loaded.

The native service retains named/global delivery order, pending named replay,
class/method callback replacement, exact sender-class filtering, delivery count,
cancellation and exception propagation. It owns its state per engine rather than
through a global singleton. Unused PEAR nesting/custom-notification-class APIs
are deliberately outside its contract; third-party extensions using those APIs
need review before release. See `tests/events/README.md` for tests and limits.

The historical vendor files remain temporarily as the differential-test oracle
and for rollback. They are no longer the engine implementation. Remove them from
source and assembled release outputs once supported-site and external-extension
checks are complete; move the required oracle into a licensed test fixture (or
retain fixed expected traces) at that point. Do not run historical dispatcher
repair scripts against the native implementation.

### Second implementation — native tag clouds, 10 October 2026

Utilities 2.019 replaces the HTML_TagCloud wrapper internals with native rendering
and shared chisimba-reborn CSS. Existing buildCloud/addElement/biuldAll callers
continue working. Catalogue browser comparison preserves all 152 non-empty labels
and destinations; the blank tag is omitted. Unsafe URLs become text and labels
are escaped; frequency uses five bounded sizes without age-based colour fading.
No tag storage or permission query changed. Tests and rollout/rollback limits are
recorded in `tests/tagcloud/README.md`. The local engine smoke confirms PEAR's tag
class is not loaded. Vendor files remain temporarily for comparison/rollback until
supported-site and external-extension checks permit package removal.

### Configuration characterisation — 10 October 2026

At the pre-cutover investigation, configuration and translation remained
on their existing implementations. The implementation below supersedes those
site/properties safety results. `tests/configuration/README.md` records the
reader/writer map, 22 reproduced observations and seven failing safety gates.
The disposable tests reproduce stale caches after replacement/append, a broken
generic setter, destructive unsupported-format failure, false success on a failed
write, Unicode loss through Config::parseConfig, and cross-document write-state
leakage. These are required fixes, not compatibility semantics for native code.
`--require-safe` exits 1 while these gates fail. Source and local runtime Config
library files match; host/container results agree. No installed configuration
values were copied into fixtures and no live save or installer was run.

The installer dependency is in `installer/steps/createconfigs.inc`, so inventories
must include `.inc` files. Catalogue reads/writes are mixed PEAR/SimpleXML/manual
XML; replacing Config alone will not make catalogue persistence safe. Prioritise
a checked file-write boundary and independent per-document state, then migrate
site XML and user INI separately. Translation remains a later, separate change.

### Third implementation — site/properties XML safety, 10 October 2026

Config 2.005 routes altconfig through native DOM XML, isolated document revisions
and checked atomic replacement. All seven original gates now pass, with 118
additional failure/roundtrip/setter assertions, a two-process race and 19 site
editor controller/form checks. Native XML preserves UTF-8 values and Latin-1
on-disk encoding using character references. Failed/stale writes preserve the
original bytes and do not publish candidate state. All specialised setters use
the same boundary; short-name, pre-login and DSN target defects are repaired.

Sysconfig 1.623 adds POST/native CSRF and cross-request revision checks for site
saves, retained drafts on failure, and contextual Help. Local browser read-only
and rejected-save journeys pass; installed configuration/catalogue hashes remain
unchanged. No successful save targeted installed settings or production.

This is not complete PEAR Config removal. Config_Container and PHPArray remain
as the temporary exposed tree compatibility contract. Installer, catalogue,
user INI, generic ini helper and translation retain their existing implementations;
the legacy XML parser's Unicode defect is not repaired for those consumers.
See tests/configuration/README.md for filesystem assumptions, tests actually run,
remaining work and deployment requirements. Do not remove the vendor package yet.


### Fourth implementation — remaining configuration callers, 10 October 2026

This supersedes the third implementation's remaining-caller list. Config 2.006
uses a native tree and XML/INI codecs without Config_Container or PHPArray.
Installer configuration/handoff, catalogue publication/discovery, user preferences,
generic INI/ADM helper, canvas and RTT callers now use native documents. Historical
`config` service requests resolve to native `altconfig` at the engine factory;
actual engine/catalogue smoke confirms no PEAR Config class is loaded.

User preference HTTP mutations and catalogue refresh use POST/native CSRF and
checked failure reporting; preference forms retain drafts and revisions. The
catalogue locks complete scan/publication/reconciliation and refuses partial,
ambiguous or stale publication. Native preference files have a version marker
and lossless quoted strings: rollback needs matching INI backups, not just old
reader code. See `tests/configuration/README.md` for precise contracts and limits.

255 assertions plus concurrency/ownership gates pass; 200 real source registrations
publish/read in a disposable catalogue. Container www-data checks, source installer
configuration step, read-only engine/DB smoke and browser routes pass. Browser
checks caught and fixed the old configuration service alias and a shared breadcrumb
layout defect. Installed config/catalogue hashes remain unchanged. Translation
remains separate. Config vendor files remain for historical tests/external audits.

User Configuration 0.812 was subsequently registered locally through the normal
module service with explicit user approval. The previous automatic-review block
is resolved. Registered list/editor/Help, draft-recovery and canvas-handoff browser
checks pass, including 390px layout and keyboard focus return. Native runtime and
preference-controller regressions pass again; anonymous access requires login;
recent PHP diagnostics are clear. Site/catalogue hashes remain unchanged.
At that local-validation stage there had been no production deployment, full
database installation or live catalogue reconciliation. The later KengaLearn
release is recorded at the top of this document and in the test README. Catalogue
refresh reconciliation in the migration fixtures remains doubled; normal module
upgrades were separately rehearsed against an isolated database and applied live.

### Fifth implementation — native translation and locale lists, 10 October 2026

Language 1.613 replaces Translation2 composition and I18Nv2 runtime use with
native lookup, canonical-connection storage and historical UTF-8 locale data.
It preserves terminology and HTML-entity boundaries while repairing Unicode,
zero-value and English-fallback defects. Checked multilingual writes preserve
other languages, roll back failures and serialise concurrent insertion; explicit
language administration retains tables unless destructive removal is requested.

7,328 installed English items match legacy output. Native lookup/facade/locale,
real isolated MariaDB failure/concurrency tests, authentication/catalogue suites,
installed engine and local browser checks pass. Full contracts and limitations
are in `tests/translation/README.md`. No production release or installed language
record rewrite occurred. The old language-admin UI is not revived by this work.
Keep Translation2/I18Nv2 vendor code for release/extension audits and historical
oracle tests; the locale JSON attribution/licence must survive later cleanup.
MDB2-to-PDO remains separate, including native language write-lock semantics.

### Translation production release — 10 October 2026

KengaLearn reopened at 15:00:56 UTC on Language 1.613. The bounded nine-file
release passed production-image fixtures, all 5,618 installed-English comparisons,
repeat registration on an isolated database and live data/configuration checks.
Rehearsal caught an obsolete Language USES entry that overwrote Security's
`word_skins`; commit `3e1aabebd` removes that collision. Translated text/pages
are unchanged after normal registration. See the translation README for exact
source manifests, backup paths, browser evidence and utf8mb3 storage limits.
The language-admin UI was not enabled and MDB2 remains the database adapter.

### MDB2-to-PDO assessment — 10 October 2026

The [caller inventory and staged migration plan](database-migration/README.md)
cover shared dbTable contracts, direct stores, security, translation, schema,
installation, operators and final dependency cleanup. Read-only live inspection
found that KengaLearn currently lacks pdo_mysql. This assessment changes no runtime
behaviour and does not mark MDB2 as migrated or removable.
