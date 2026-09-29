# PEAR dependency register

Status: static source inventory, 29 September 2026. A direct load proves that
the framework can use a component; it does not prove that the corresponding
module is installed, enabled or reached in KengaLearn production. Do not
remove a package from `app/lib/pear` until runtime registration and supported
deployment checks have been recorded.

## Bootstrap and persistence boundary

| Component | Direct framework caller | Present role | Replacement direction | Removal gate |
| --- | --- | --- | --- | --- |
| LiveUser / LiveUser_Admin | `app/classes/core/engine_class_inc.php` loads both at every engine bootstrap and initialises the singleton | Legacy identity, permission application setup and compatibility callbacks; native-auth services coexist | First decouple engine bootstrap from LiveUser, keeping an adapter only for proven callers | Native-auth login, remembered login, logout, identity lookup, groups and permission setup pass with LiveUser excluded |
| MDB2 / MDB2_Schema | `engine_class_inc.php`, `dbtable`, `dbtablemanager` | Configurable database abstraction and schema compatibility | Establish PDO as the canonical path; migrate API-by-API rather than replacing calls mechanically | No supported setting selects `MDB2`; schema install/upgrade tools and regression suite use PDO |
| PEAR / PEAR_Error | Engine error callback, MDB2 error checks and historical base classes | Error representation coupled to MDB2 and LiveUser | Use native exceptions/results at adapter boundaries | MDB2/LiveUser removed and no active code extends or type-checks PEAR classes |
| Config | `core_modules/config/classes/ini_class_inc.php` | Configuration-format compatibility | Map XML/INI readers and installer behaviour before selecting a maintained configuration loader | All installer and deployed config reads have compatible tests and migration/rollback steps |

## Module-scoped direct loaders

| Component | Direct caller(s) | Exposure to establish | Direction |
| --- | --- | --- | --- |
| Mail / Mail_mime | `core_modules/mail/classes/mailer_class_inc.php` | Whether legacy `mail` remains registered and sends production messages | Keep isolated; route new sending through Communications. Retire only after delivery, attachment and bounce/rollback tests |
| Translation2 / Translation2_Admin | `core_modules/language/classes/languageconfig_class_inc.php` | Active locale administration and fallback paths | Separate high-regression localisation migration |
| XML/RPC | `core_modules/packages/classes/rpcserver_class_inc.php`; `core_modules/api/classes/xmlrpcapi_class_inc.php`; filter helpers | Registered routes, public endpoint exposure and inbound authentication | Disable unneeded endpoints first; replace needed APIs with maintained HTTP/JSON endpoints |
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

## Near-term sequencing

1. Finish native-auth versus LiveUser reachability mapping without changing
   current production bootstrapping.
2. Inventory the production module catalogue for legacy Mail, XML/RPC,
   packages and API routes.
3. Make PDO compatibility testable in a dedicated branch; do not flip the
   production database abstraction as part of a security hotfix.
4. After that evidence, isolate the smallest inactive database-driver or
   test-only package set in a reversible removal change.
