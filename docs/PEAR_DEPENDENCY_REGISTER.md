# PEAR dependency register

## Complete remaining-tree audit — 12 October follow-up

Approved follow-up: Derek authorised removal of the dormant mail/network cluster.
The earlier automatic-review hold is resolved; production remains unchanged.
Both complete PDO/MDB2 regression suites, MIME/privacy fixtures and loopback SMTP
acceptance/rejection tests pass after this removal. Local Communications and Site
Administration render without new PHP web diagnostics. No real email was sent.
Local removed files have rollback copies in container directory
`/tmp/chisimba-paused-mail-20261012`.

This section supersedes the earlier retained-package lists below. Production is
unchanged. Derek has renamed `maillist` to `maillist__PAUSED`; all eleven files are
byte-identical. Neither it nor `blog__PAUSED` should be revived without replacing
legacy dependencies and checking PHP 8, persistence and permissions.

Removed unused vendor groups: Crypt/Rc4, File (Archive/Gettext/MARC/Bittorrent),
HTML QuickForm/Common, Image Canvas, OpenDocument, PHP CodeSniffer/CompatInfo/
DocBlockGenerator, Text Diff/Wiki/LanguageDetect/helpers, MIME, Log, Console,
System, OS, Structures, Calendar and PEAR package-manager internals/commands.
Associated vendor data for removed groups is also removed. Only the base `PEAR.php` remains for identified compatibility consumers. Full
removed-path inventory: `tests/dependencies/retired-pear-paths.json`. Original
source/licences remain in Git parent `c1e7774b8`; no vendor rewriting was done.
Calendar's names in module labels and Log's method names are not library loads.
Chisimba's calendar and logging implementations are independent of these packages.

The dev-environment pack and builder now omit Calendar and install only PEAR's
base/error class, not its package manager or exception class. Existing runtimes need
the same removals; additive copying is insufficient. The pack's twelve required
files are present in source and the curated pack. Build syntax passes; no network
rebuild or upstream version change was performed.

### Exact remaining groups and removal gates

| Group | Remaining source evidence | Gate |
| --- | --- | --- |
| MDB2, XML Parser/Serializer/Util, PEAR base/error | dbTable's retained MDB2 branch and MDB2 Schema Parser2 | Migrate remaining sites to PDO, then retire the shared fallback and its tests deliberately |
| XML RPC | Core blog/screenshot filters, API/packages backing classes; CMS, Simplegal, IM and paused Blog | Audit supported integrations/content and retire or replace each caller; retired public routes remain retired |
| Archive/Tar | Bundled Zend Filter Compress Tar | Establish supported Zend archive consumers before dropping the adapter/dependency |
| RDF, MDB (pre-MDB2) | FOAF parser loads RDF; RDF's optional database store loads MDB | Modernise or retire the FOAF/RDF path and database store; not covered by PDO conversion of dbTable |
| DB (pre-MDB2) | Historical installer secondarydetails.inc imports DB.php; OpenID SQLStore recognises db_common; bundled ADOdb compatibility/examples | Remove unused installer/adapter paths after confirming reachability; do not confuse these with MDB2 |
| Validate | FOAF validation | Replace its used validation contract if FOAF is retained |
| Mail, Net, Auth, Cache, PEAR Exception | Paused maillist imports MIME decoding/POP3; paused Blog uses Net DNSBL; vendor SMTP uses Auth SASL, DNS uses Cache, URL mapper uses PEAR Exception | Removed after Derek explicitly approved on 12 October; both paused modules require replacement dependencies before revival |
| data | MDB2, driver, RDF and XML Serializer supporting files | Remove together with their owning libraries |

Correction to the initial audit: Mail/mimeDecode.php was present; Net/POP3.php
was already absent. With explicit approval, Mail, Net, Auth/SASL, Cache/Lite,
PEAR/Exception and Mail_Mime data are now removed together. The shared modern
PHPMailer service is unaffected. Do not revive Blog or Maillist by restoring old
network libraries; replace their required functionality through maintained services.

### Method and limitations

`php tools/dependencies/audit-pear.php /path/to/modules` inventories class and
literal include references, ignoring PHP comments and including nested vendors,
paused modules and SQL/PHP schema files. Its conservative output includes ordinary
words such as `calendar`, `log`, `db` and MIME metadata keys; these were manually
checked. Broader textual/include/build scans supplemented it. It does not prove
arbitrary computed class names, external extensions or per-site runtime usage.
No library was removed solely because a single direct-import scan was empty.
The source scan is reproducible; local before/after JSON evidence is under
`work/pear-cleanup-20261012/remaining-pear-*.json` in the workspace.

Validation: complete PDO/MDB2 disposable suites pass, including native installer
creation of 107 definitions, updates/repeats, rollback and race checks. Native
configuration, translation, XML, BBCode, MIME and loopback SMTP checks pass.
Ownership check passes in its required root container context. Tests requiring an
installed fixture were not counted as passes when invoked without their opt-in.
No real mail was sent. The reduced local runtime renders administration and the
system-log page without new web PHP diagnostics. Retired runtime files have a
container-local rollback copy at `/tmp/chisimba-unused-pear-20261012`.
Remaining production rollout is separate.

## Current local state — 12 October 2026

This section supersedes dated remaining-work notes below. Production is unchanged.

- Mail 1.006 removes PEAR Mail/Mail_mime from the shared `mailer` facade. The
  pinned PHPMailer MIME service preserves synchronous acceptance, To/CC/private
  BCC and configured HTML alternatives, with shared transport configuration.
  New workflow migrations to Communications remain separate from PEAR removal.
  See `tests/mail/README.md` for recipient limits, transport restrictions and the
  deliberately unsupported historical attachment method. Tests sent no real mail.
- Config, Event Dispatcher, Translation2, I18Nv2, HTML TagCloud and HTML BBCodeParser
  have moved from `app/lib/pear` into licensed CLI-only comparison fixtures under
  `tests/fixtures/pear-oracle`. They remain in Git for tests, **not in app releases**.
  Full PHP application/module scanning found no imports outside their old vendor
  directories (the engine only contains a historical comment).
- Utilities 2.023 retires the unused `graph` wrapper and PEAR Image_Graph package.
  No application/module graph consumer was found. The wrapper was not PHP 8
  parseable and suppressed diagnostics globally; no new renderer is invented for
  an unused API. Recover it from Git only for a deliberate consumer migration.
- Filters 1.564 drops the unused XML-RPC include in `parse4wpresent`; its API stub
  contains no XML-RPC implementation. Actual remote blog/screenshot filters and
  legacy RPC support still require their own product/data audit.
- The installer validates native configuration/translation/XML/BBCode, PHPMailer
  and getID3 files, no longer the migrated PEAR packages. Missing files and missing
  PDO MySQL still block installation. The PEAR web installer and channel packager
  refuse with HTTP 410 / CLI 64 before loading any package-manager code.
- The fresh-install test exposed an unrelated long-server-ID defect. Shared
  dbTable now uses a 32-character random ID when the historical site-prefixed ID
  would overflow VARCHAR(32). Existing short IDs and explicitly supplied IDs are
  unchanged; existing data/schema are untouched. Both PDO/MDB2 fixtures cover it.

### Unused development packages — 12 October follow-up

Removed `app/lib/pear/Benchmark` (3 files), `app/lib/pear/PHPUnit2`
(40 files), and the obsolete `app/tests` harness (57 files). Benchmark has no
identified external caller. PHPUnit2's only application callers were eight files
in that historical harness. The harness also contains PHPUnit 1-era MDB2 browser
tests, hard-coded `/var/www/5ive` paths and direct-driver test setup. None of the
current regression runners consumes it; `build.xml` already excluded `app/tests`.
Modern `tests/database` PDO/MDB2 and `tests/configuration` checks remain intact.

Evidence: case-sensitive source/class/include scans across the complete framework
and modules trees (including vendor resources), transitive PEAR library scans,
and dev-environment scripts/manifests. References outside the removed trees are
historical reports, the old build exclusion and the retirement guard. Neither
removed package occurs in the curated compatibility-pack manifest. Assemblers
copy `framework/app`, so fresh assemblies inherit the removals. Existing runtimes
must explicitly remove these three paths; additive copying alone is insufficient.

The exact historical source and licence notices remain recoverable from parent
commit `c10256cce` (PHPUnit2 BSD notices; Benchmark contains historical BSD/PHP
licence wording). These are removals, not modified vendor redistributions. No
security-advisory claim is made and no external extensions were tested.

Remaining caller audit confirms `maillist` loads Net/POP3 and Mail/mimeDecode;
FOAF loads Validate; paused blog, CMS, simplegal and core filters/backing RPC
classes still reference XML/RPC. Vendor Mail is also reachable through Log and
File_Archive. Calendar, old DB/MDB drivers and package-manager internals require
further transitive/build analysis; absence from a direct application scan is not
enough to delete them. MDB2 and its schema/XML dependencies remain untouched.

Validation after removal: current complete PDO/MDB2 disposable database suites
passed, including native installer creation of 107 registered definitions, schema
upgrade/repeat checks and rollback/race tests. The retirement guard and catalogue
route test pass. A case-insensitive post-removal PHP/INC/CONF scan across app and
modules finds no PHPUnit2 or Benchmark class/path references. The three obsolete
paths were moved out of the running local PHP 8.5 web root into container-local
`/tmp/chisimba-retired-development-20261012` for rollback. Browser catalogue and
Site Administration render successfully; local web logs contain no new PHP
warnings, deprecations or fatal errors. No production files or data were changed.

### Deliberately retained compatibility

Do not delete the entire PEAR directory. MDB2 and MDB2_Schema remain for sites
awaiting PDO; Schema/Parser2 imports XML_Unserializer, so XML Serializer/Parser/Util
cannot yet be removed. Mail vendor files also serve dormant maillist MIME decoding,
File_Archive's mail writer and Log's mail backend. Calendar and other legacy module
libraries need per-consumer revival/retirement decisions. XML-RPC remains in old
client/filter/backing classes even though public api/packages routes stay retired.
The legacy blog directory was renamed by Derek to `blog__PAUSED`; SimpleBlog is
separate and unaffected. Renaming source does not unregister a deployed module.

Module Catalogue 3.141 retires the manual ZIP/TAR uploader with Derek's approval.
The upload controls and extraction code are removed; the former administrator
route returns HTTP 410 before inspecting files or changing catalogue state.
Normal installation/updates of deployed modules remain available. Contextual Help
explains deployment from versioned source, catalogue refresh, then installation.
Archive vendor files remain pending an audit of their other legacy consumers.

The focused retirement test covers GET/POST, forged uploads, administrator access,
untouched upload bytes and no archive parser loading. Catalogue discovery, view
filters, dependency updates and batch-install redirect checks pass. The local
PHP 8.5 browser update from 3.139 to 3.141 succeeded; the retirement message,
quick/full Help and Escape were verified. Production remains unchanged.

### Validation and release requirements

Strict MIME fixtures, a loopback SMTP capture (success/rejection/BCC privacy),
mocked SendGrid payload tests, 18 native/legacy configuration/translation/tag-cloud/
XML/BBCode fixtures, event oracle/native checks, installer requirements and complete
PDO/MDB2 contracts pass. The new disposable PHP 8.5 site at localhost:8087 completed
schema creation and, after the ID fix, initial registration and admin login.
Administration and installed-module catalogue render; the real installed mail and
BBCode services compose without PEAR. All 2,724 installed English translations
match the retained oracle. No PHP warning/deprecation/fatal appeared in its logs.
The installer reports optional calendar/imap extensions missing; these are not PHP
warnings and those optional features were not tested.

Deploy a complete versioned release so removed files do not survive additive copy
operations. Never ship tests/fixtures. Preserve current site configuration and
check each site's transport and external extensions before release. Run
`php tests/dependencies/retired_runtime_test.php` against source/assembly changes
to catch accidental reintroduction. The existing curated compatibility pack does
not contain the six moved packages or Image_Graph; no download/build was needed.


Status: native events, tag clouds and configuration were released to KengaLearn
on 10 October 2026; native translation/locale lookup followed the same day.
Translation release: `b97ac37d4` plus `3e1aabebd` over the earlier framework
`6e101d2ee` / modules `9cdde04de` baseline. Evidence and rollback details are in
`tests/configuration/README.md` and `tests/translation/README.md`. PDO was deployed
to chisimba.com, KengaLearn and LearnTheBirds on 11 October; see the
[rollout record](database-migration/production-rollout-20261011.md). Other sites
still await migration. The language-administration interface and final vendor
cleanup remain separate.
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

The local MySQL/MariaDB PDO migration passed acceptance on 11 October 2026.
See [local acceptance](database-migration/local-acceptance-20261011.md) for the
shared dbTable/schema implementation, caller migrations, both-driver regression
checks and PHP 8 warning cleanup. The local site now selects PDO. Production
rollout is staged separately; MDB2 remains available for sites not yet migrated.

PDO does not mean every module is PEAR-free. The local core bootstrap no longer
loads PEAR/PEAR_Error after the 11 October cleanup below.
Mail/Mail_mime, BBCode parsing, XML serialisation, Archive_Tar and historical
XML-RPC/graph helpers still have callers. Audience 0.017 now uses native dbTable repositories and is deployed on
LearnTheBirds. Seven completed staging operators are retired; the two reusable
schema operators now use dbTable. Historical BlogImporter still needs a separate
direct-driver migration decision. Do not remove the vendor directory or claim all CLI/extension
paths have migrated. Before enabling a dormant module on any site, audit its
database calls and PHP 8 compatibility, then test its actual workflows.

## Bootstrap and persistence boundary

| Component | Direct framework caller | Present role | Replacement direction | Removal gate |
| --- | --- | --- | --- | --- |
| LiveUser / LiveUser_Admin | None: bootstrap and package files removed | Retired; native-auth is the supported identity boundary | Complete in source | Focused native-auth/session/logout contracts pass; production journey verification is pending release |
| MDB2 / MDB2_Schema | `engine_class_inc.php`, `dbtable`, `dbtablemanager` | Configurable database abstraction and schema compatibility | Establish PDO as the canonical path; migrate API-by-API rather than replacing calls mechanically | No supported setting selects `MDB2`; schema install/upgrade tools and regression suite use PDO |
| PEAR / PEAR_Error | MDB2 compatibility checks and legacy module consumers | Native PDO bootstrap no longer loads PEAR; native exceptions remain redacted | Local cleanup implemented; retain recognition of existing MDB2 errors without loading PEAR | Remaining supported MDB2 sites and other PEAR consumers migrated |
| Config | No identified active first-party loader after native migration | Vendor retained for legacy fixtures/external-extension audit | Native XML/INI documents and checked writers now serve all identified callers | Deployment/extension audit and full database installation before vendor removal |

## Module-scoped direct loaders

| Component | Direct caller(s) | Exposure to establish | Direction |
| --- | --- | --- | --- |
| Mail / Mail_mime | `core_modules/mail/classes/mailer_class_inc.php` | Whether legacy `mail` remains registered and sends production messages | Keep isolated; route new sending through Communications. Retire only after delivery, attachment and bounce/rollback tests |
| Translation2 / Translation2_Admin / I18Nv2 | No identified active first-party loader after Language 1.613 | Native runtime released to KengaLearn; external-extension/installer audits outstanding | Retain vendors until release/cleanup gates pass; see translation test README |
| XML/RPC | `core_modules/packages/classes/rpcserver_class_inc.php`; `core_modules/api/classes/xmlrpcapi_class_inc.php`; filter helpers | **F-006 update, 30 September:** KengaLearn's `api` and `packages` routes return HTTP 410 without loading services. ADM is retired. Backing classes and shared libraries remain; other sites require separate verification. | Do not restore legacy endpoints. Inventory internal/filter consumers before deleting XML/RPC libraries. Future integrations should use explicitly authenticated and authorised HTTP/JSON services. |
| HTML_BBCodeParser | Shared wrapper migrated to native formatting in Utilities 2.021 | Retained vendor fixtures and external/dormant extension audit; site content rollout gates below | Remove vendor/build entries only after those gates |
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


## Core bootstrap and operator cleanup — 11 October 2026

Local change, not yet deployed to production. The framework errorhandler is a
native message adapter: no PEAR inheritance, include or newly constructed
PEAR_Error. It accepts Throwable and already-created legacy errors. Database
manager initialisation and catalogue delete-row checks no longer invoke the PEAR
class unconditionally. MDB2's own callbacks remain inside its selected backend.
PDO database exceptions and their redacted failure contract are unchanged.

Seven hard-coded operators for the completed LearnTheBirds staging move now
refuse to execute before application bootstrap. See the modules repository's
`docs/retired-staging-imports.md` for their exact paths and recovery commit.
Reusable SitePages composition and Payment contribution schema commands now use
module dbTable repositories and ChisimbaDatabaseSchema, preserving check/apply
behaviour and guarding DDL against an existing transaction. No production import,
payment, registration or email was executed for this cleanup.

Validation covers native error/fallback messages without PEAR, existing legacy
errors without callback side effects, refusal of all retired --apply commands,
real PDO and MDB2 maintenance dry-run/apply/repeat, content and identity
preservation, missing-table errors, transaction ownership, full PDO/MDB2
contracts and PDO engine composition including database-manager initialisation.
The disposable installed site's engine/catalogue/webinar repositories also boot
without PEAR or MDB2. The fresh-install runtime smoke now asserts those classes
are absent. Remaining Mail/XML/BBCode/archive consumers still require their own
migration; vendor files and the MDB2 option are intentionally retained.

## Mail and content follow-up — 11 October 2026

Local source changes; **not deployed**. Retained vendor files are not evidence of
active use. Conversely, removing a wrapper's dependency does not make the entire
vendor package removable: dormant modules, other sites, tests and build manifests
still need to be checked.

### Completed first migration slice

- File Manager folder/file alerts now queue through Communications, using its
  configured sender and delivery controls. Duplicate addresses across student and
  instructor groups receive one personalised message. The service returns false
  if any queue request fails. Existing controllers do not present that return to
  the user; queued acceptance is not proof of delivery. There are no attachments,
  CC or BCC in these two workflows. File Manager declares Communications as a
  dependency; its worker must be configured before enabling alerts on a site.
- Utilities `xmlserial` now uses PHP DOM for local files and array/scalar XML.
  Existing default PEAR metadata shapes remain readable: scalar values normally
  read as strings, empty arrays/elements as empty strings, repeated tags as lists,
  mixed text under `_content`, and numeric/invalid output keys under
  `XML_Serializer_Tag`. This intentionally preserves the old lossy contract rather
  than changing stored formats. Empty-element whitespace is not byte-identical.
- XML inputs are limited to 8 MiB, 64 levels and 100,000 elements. DTDs, remote
  filename URLs, object/class deserialisation and invalid XML text are rejected
  with exceptions. Object deserialisation is deliberately unsupported; ETD and
  triplestore users must check their stored fixtures before revival/deployment.
- Two shell backticks in bundled getID3 were replaced with equivalent shell_exec
  calls to permit strict PHP 8.5 class loading. This is a local vendor patch, not
  an upstream upgrade or a claim that all media parsing is modernised.

Validation: native XML fixtures under strict PHP 8.5, bidirectional comparison
with retained PEAR XML, invalid/DTD/object/depth/size rejection, local-file input,
File Manager queue/dedup/failure fixtures, retained XML parser/BBCode boundary
regressions, normal local catalogue updates, real engine/media-analyser composition
and authenticated local File Manager page. The oracle comparison alone tolerates
PEAR vendor E_DEPRECATED diagnostics; native tests do not suppress diagnostics.

**Outstanding media gate:** a synthetic WAV through the full old getID3 analyser
fails strict PHP 8.5 at `getID3::$info` dynamic-property creation. Its wrapper also
suppresses reporting and restores it from the wrong setting, and the analyser
still has a PHP 4 constructor. Do not claim a clean full media-upload regression.
Modernise/replace that retained media dependency and test supported formats before
rolling this slice out to production. It is separate from PEAR XML but encountered
on the same File Manager path.

### Remaining work in steps 3 and 4

1. Legacy mailer remains in security/user registration, error reporting, utilities
   date reminders and older forms, practicals, assignment, discussion, CMS/blog
   and comment modules. Trace active workflows before changing delivery semantics.
   Forms uses CC; discussion and CMS flag notifications use BCC. Communications
   currently handles one recipient per outbox message, so preserve visible CC and
   private BCC semantics explicitly rather than silently discarding fields.
   The legacy attachment method references an uninitialised base mail object;
   no active first-party attachment invocation was found in this audit. Verify
   extensions before retiring it. Use the already-pinned PHPMailer boundary if
   MIME support is needed; do not implement another mail protocol stack.
2. BBCode's shared wrapper serves washout, older blog and catalogue descriptions.
   Its filters include Basic, Extended, Images, Lists, Email and Links. Capture
   saved-content fixtures for nested/malformed tags, URLs, images and mixed HTML,
   and trace sanitisation at each output before replacing it. Its relative INI
   lookup also needs correcting as part of that migration.
3. Module Catalogue still exposes archive upload/extraction behind administration.
   The non-ZIP branch invokes Archive_Tar; ZIP uses the old wzip service. Replacing
   TAR alone would leave the shared installation risks: establish method/CSRF,
   trusted package policy, traversal/link/size checks, staging and rollback before
   adopting native archive facilities. No archive was extracted in this audit.
4. XML-RPC backing/filter consumers and Image_Graph helpers remain. Retired public
   API routes must stay retired; audit internal callers before deleting libraries.

LearnTheBirds webinar reminders, production mail queues and production files were
not changed. Vendor deletion/build-pack pruning remains a separate final stage.

### Media gate follow-up — getID3 1.9.27

The preceding getID3 gate is resolved for the seven fixture formats in
`tests/media/README.md`. Files now pins official stable 1.9.27 with unmodified
vendor checksums/licences; the temporary backtick patch is superseded by upstream.
The wrapper no longer suppresses diagnostics. File Manager now uses upstream Ogg
analysis, preserves plain UTF-8 titles and excludes raw binary chunks from textual
XML metadata. Strict PHP 8.5 isolated and installed-runtime checks passed for WAV,
MP3, FLAC, Ogg/Vorbis, PNG, JPEG and MP4, plus invalid/missing-file checks. A synthetic
MP3 also passed the local browser upload and details workflow. This does not claim
all codecs or legacy modules have been tested. No production deployment yet.

### Native BBCode — 11 October 2026

Utilities 2.021 replaces the shared BBCode wrapper's PEAR loader and relative INI
lookup with a bounded native formatter. See `tests/utilities/README.md` for the
supported grammar, intentional compatibility differences and HTML sanitisation
boundary. Strict PHP 8.5 tests cover retained legacy fixtures, nested/malformed
formatting, generated URL/style safety, Unicode, limits, working-directory
independence and washout delegation. The installed PDO engine composes the wrapper
without PEAR, and the local installed-module catalogue renders its descriptions.
Old blog/custom-filter stored-content comparison remains a per-site rollout gate;
no production content was changed and no PEAR vendor files were deleted.

### Content-expiry notices — 11 October 2026

Utilities 2.022 routes dateandtime's retained notification API through Communications
and declares the dependency. It resolves logical user IDs through Security's
userservice, uses the configured site sender and intended subject, and corrects
the old date format's month/minute mix-up. It records a timestamp only after queue
acceptance; acceptance is not delivery. A key derived from the content identity,
recipient and previous timestamp deduplicates retries when timestamp persistence
fails. Invalid/missing content or recipients do not queue. Real outbox/content
fixtures run on PDO and MDB2, including storage failure, cooldown and later notices.

No active first-party sendExpiredMsg callers were found in this audit; this keeps
the reusable API correct without enabling any dormant module or scheduler. The
old optional explicit-address mailUser API remains; legacy placeholder addresses
resolve by logical user ID. Message bodies are plain text. Other legacy mail
callers, CC/BCC compatibility and archive installation remain pending. Production
was not changed and tests did not send email.
