# Native translation migration — Language 1.613

Source/local validation: 10 October 2026. This change has not been deployed to
KengaLearn. The database adapter remains MDB2.

## Behaviour and callers

`languageConfig` now composes `ChisimbaTranslation` with the shared
`translationstore` through the engine's canonical database connection. No private
DSN, Translation2 decorators or I18Nv2 classes are loaded by the language module.
Existing `languageText`, `code2Txt`, systext abstractions, language selection,
list APIs and language-administration facade methods use this implementation.
Module Catalogue continues to install registered language items through its
existing database service. There is no automatic schema migration on page loads.

Page-prefetch caches remain isolated by language and page, and are invalidated
by successful native writes. HTML entities remain the historical output boundary;
Unicode is no longer decoded through Latin-1. Missing/empty translations fall
back to English, then the explicit default or item identifier; the text `0` is
valid. These deliberately repair legacy Unicode corruption, zero-value loss and
English fallback blocked by the old decorator order. Existing facade backslash
handling and terminology substitution remain unchanged.

Language metadata may be empty on older installations: implicit English reads
still work without inserting registry rows. Codes are lower-case, two or three
letters with optional underscore-separated regional components (e.g. `pt_br`).
Malformed/unregistered request selections fall back to English. Native writes
reject malformed codes, invalid UTF-8, nontransactional translation tables and
nested transactions before mutation. Multilingual saves update only submitted
languages in one transaction. A database-scoped advisory lock serialises native
writes, including concurrent insertion into old tables without unique keys.
The lock is implemented for the currently supported MySQL/MariaDB adapters;
the later PDO migration must provide equivalent semantics. Catalogue writes
retain their existing module-installation/maintenance boundary.

Explicit language creation uses the MDB2 schema manager and creates an InnoDB,
utf8mb4 table. Unregistering retains translations by default; re-registration
reuses a compatible retained table. Explicit destructive removal is still
supported, but English cannot be removed. MySQL DDL is not transactional:
creation can leave an unregistered table if registry insertion fails, and forced
removal can leave stale metadata if its subsequent deletion fails. Operations
report failure; creation can be retried against the retained table. Administrative
DDL needs a backup and maintenance boundary. Normal text saves have rollback
coverage. The storage service is not an HTTP authorisation/CSRF boundary.

`languagecode` loads a UTF-8 JSON snapshot of the 88 historical country and 88
language-name lists, retaining codes and attribution. It provides native escaped
select markup and preserves the public `iso_639_2_tags->codes` shape. Country
selection does not leak between successive renders; alphabetical selects retain
the `input_country` label target. `getISO` also resolves English names. No Intl
extension is required (the tested web image does not have it). These historic
names are intentionally not a geopolitical data refresh. Licence/notice files
must accompany the JSON. The unused locale formatter object is now language/
country metadata: process-global locale changes are no longer performed. No
first-party consumers of that formatter were found. `autoConv` is output-only,
accepts explicitly supported encodings and does not rewrite request parameters.

This is the inspected Chisimba API, not an implementation of every Translation2
or I18Nv2 API. Audit external extensions before removing vendor packages. The
old `langadmin` HTTP UI was not revived or activated; its authorisation, CSRF,
Help and browser workflows require separate modernisation before supported use.
Its shared facade storage methods are exercised in the disposable database.

## Repeatable checks

From the framework repository:

```sh
php tests/translation/legacy_contract_test.php
php tests/translation/native_lookup_test.php
php tests/translation/language_boundary_test.php
php tests/translation/locale_names_test.php
python3 tests/translation/run_storage_test.py
```

The last command requires Docker, the locally built `chisimba-php85-web` image,
and `mariadb:10.11.18`. It creates a uniquely named internal network and temporary
MariaDB container with tmpfs data, no published port and a random fixture password.
The PHP source is mounted read-only. No installed site database is reachable.
Resources are removed in `finally`. The test covers creation, UTF-8/emoji,
regional codes, metadata updates, empty/null pages, other-language preservation,
cache invalidation, injection rejection, failed-write rollback, nontransactional
table rejection, unregister/re-register, explicit removal, protected English and
two independent concurrent writer processes without a unique item index.

`runtime_smoke.php` and `installed_parity_test.php` need an actual configured app
path as argv[1], a suitable web-user execution context and `TRANSLATION_SMOKE=1`.
They read installed records without modifying translation data. For the local
container, run from the workspace root:

```sh
docker exec -i -u www-data -e TRANSLATION_SMOKE=1 chisimba-php85-web php -d auto_prepend_file= /dev/stdin /var/www/html/ch < framework/tests/translation/runtime_smoke.php
docker exec -i -u www-data -e TRANSLATION_SMOKE=1 chisimba-php85-web php -d auto_prepend_file= /dev/stdin /var/www/html/ch < framework/tests/translation/installed_parity_test.php
```

The parity check intentionally loads the retained legacy implementation as an
oracle. The runtime smoke instead rejects any included Translation2/I18Nv2 file.
Remove the legacy-only tests at final vendor cleanup; retain native regression
coverage. Neither check certifies a fresh full-database installation.

## Evidence and release boundary

- PHP 8.5.11 local web runtime: native engine/language/locale smoke passed without
  Translation2/I18Nv2; existing native config/catalogue/event composition passed.
- All 7,328 installed English translations matched legacy output exactly.
  Synthetic fixtures cover the Unicode, zero and fallback repairs absent there.
- 60 translation, native-authentication and catalogue regression scripts passed;
  changed/new PHP syntax and isolated database tests passed.
- Read-only Chrome journeys: module catalogue and language selector, personal/site
  administration and configured Classes terminology, profile country selection,
  user preferences and full Help with Escape, system configuration and Active
  Knowledge Maps. Browser console and recent container PHP diagnostics were clear.
- Installed translation tables and configuration were not saved or upgraded.
  KengaLearn was not changed. Language registration remains at its prior version
  until the normal module-update path is run during release preparation.

Before production: preserve a code/database backup, inspect deployed source drift
and language registrations/table encodings, rehearse normal Language 1.613 module
registration against a disposable copy, and run the production release smoke
under an authorised window. Ship JSON and licence resources with source. There
is no translation-record format rewrite to reverse; restore matched code/module
metadata on rollback. Keep the PEAR vendor-removal gate in
`docs/PEAR_DEPENDENCY_REGISTER.md` open until deployment/extension/installer audits
and the remaining dependency migrations are complete.

## Release rehearsal finding

The KengaLearn rehearsal detected a historical `word_skins` ownership collision:
Language declared it as a global USES entry while Security owns its TEXT entry.
Normal Language registration moved that Security translation to the system page
and changed its text. The obsolete Language USES declaration is removed; no
first-party caller uses it. Release checks compare translation identifiers, pages
and text, plus legacy language descriptions independently of regenerated registry
row IDs.
