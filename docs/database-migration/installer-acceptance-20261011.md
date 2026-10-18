# Fresh PDO installer acceptance — 2026-10-11

The installer no longer asks for a system PEAR location or suggests bypassing
missing libraries. It uses the application's bundled `lib/pear` path, checks the
remaining compatibility files, and requires native PDO MySQL. MDB2 and a PEAR
registry are not installation prerequisites. `KEWL_PEAR_PATH` remains in generated
configuration because error handling, mail, archive, XML and BBCode consumers
still use bundled compatibility libraries. This is not a claim that all PEAR
code has been removed.

The current PHP baseline is shown as 8.2+, the database selection says
“MySQL / MariaDB (PDO)”, and connection confirmation no longer prints the DSN or
password. Requirements are checked again on confirmation; neither a posted PEAR
path nor a fabricated confirmation skips failed checks. SimpleXML is required
for seed loading; ZipArchive is used to detect ZIP support.

## Fresh-install findings fixed

- The wizard declared dynamic properties and rendered null form values; these
  are now explicit properties/empty values, without suppressing warnings.
- Initial XML seed loading used an old warning-producing converter. The installer
  now reads the row/field XML structure using SimpleXML and scalar strings.
- Catalogue seed loading passed SimpleXML objects to bound PDO values; conversion
  now happens at the XML boundary. The shared driver remains strict.
- Catalogue table creation discarded declared index names. One context field
  generated an index name longer than MySQL permits. The declared name is retained.
- Context and module-block tables are now created before the controller/catalogue
  bootstrap that reads them. Empty initial terminology/block lists are handled.
- The initial registration redirects to native `showlogin`, without submitting
  default credentials in a URL or causing a CSRF error.
- Registration Service is part of the basic installation, so native sign-in's
  recovery link and its language items are available with declared dependencies.
- Three application-module schema files emitted whitespace after `?>`, preventing
  the final redirect. Their closing tags were removed: account-event-service,
  legal-acceptance-service and registration-service.
- The obsolete missing print stylesheet reference was removed, and installer
  HTML declares UTF-8.

## Acceptance evidence

A separate local PHP 8.5.11 / MariaDB 10.11.18 installation was built from the source
application bundle. It had a blank tmpfs database, synthetic site settings, no
live uploads/credentials and no outbound network. Browser access used a
127.0.0.1-only relay. Existing local database/container state and production sites
were not changed by the installation test.

The full browser workflow was exercised: directories, site details, requirements,
real PDO database connection, configuration creation, initial tables/seed data,
first catalogue registration, administrator sign-in, administration, catalogue and
user management. Failed development attempts were followed by an empty-database
rerun; final success did not depend on partially registered modules.

The successful clean run installed **49 modules**. The runtime smoke confirmed
`ChisimbaDatabaseConnection`, no loaded MDB2/PEAR Config/Translation2, native recovery
translation, and the declared context index. PHP logs were empty across successful
installation, first registration, login and the checked administration pages.
Calendar and IMAP are optional extension notices, not PHP runtime warnings.

Negative browser test: temporarily remove bundled `XML/Parser.php` in the
**disposable copy**. Requirements remain blocked and name the missing file.
Restoring it allows the check to pass. CLI tests also reject missing PDO MySQL and
forged requirement confirmation.

Validation: 17 focused installer/configuration/catalogue suites under strict PHP
warning handling; the missing-PDO-MySQL `php -n` case; full isolated PDO contracts,
including 107 schema definitions; retained MDB2 regression contracts; 25 changed-file PHP lint and Git whitespace checks.
A pre-existing configuration fixture needed its connection probe stub restored
because it is a file-publication test, not a real-database test. The actual browser
installation and PDO suites independently exercise connection validation.

This work has not been deployed to production. The disposable site is available
at `http://127.0.0.1:8086/ch/` while its containers and relay are running. Operational
instructions and exact disposable names are in `dev-environment/tests/installer/README.md`.
Historical error-page/CAPTCHA components encountered during failed development
attempts were not broadly modernised; the clean accepted flow does not use them.
