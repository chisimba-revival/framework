# Native installer regression checks

Run on the supported PHP runtime:

```sh
php app/installer/tests/native_dsn_test.php
php tests/installer/requirements_test.php
php -n tests/installer/requirements_test.php
php tests/installer/catalogue_seed_test.php
php tests/configuration/installer_configuration_test.php
python3 tests/database/run-pdo-contracts.py
```

- Requirements: bundled libraries, no posted/system PEAR override, missing-file
  rejection, confirmation bypass rejection, and actual PDO driver detection.
  The `-n` run verifies a runtime with no PDO MySQL driver is rejected.
- Catalogue: actual XML seed loader preserves scalar values, Unicode, empty values
  and repeated loads; actual table creation honours the declared index name.
- Configuration: real checked file publication in a temporary root, with a stubbed
  connection probe. Real connections are covered by the PDO integration and browser
  installation tests; this fixture must not require a live site's credentials.
- `installed_runtime_smoke.php`: explicitly opted-in, read-only verification of a
  disposable completed installation. Requires `CHISIMBA_INSTALLER_FIXTURE=1` and
  the installed application directory as its argument.

See the sibling dev-environment repository's `tests/installer/README.md` for a
fresh isolated browser test, and `docs/database-migration/installer-acceptance-20261011.md`
for the completed acceptance scope.

Native DSN repair tests cover defaults, encoded credentials, ports, IPv6, Unix
sockets, array input, query isolation and reconstruction through the PDO boundary.
They do not require PEAR DB or connect to a database.
