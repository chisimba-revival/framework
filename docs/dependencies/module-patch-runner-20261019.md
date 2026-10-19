# Module Catalogue XML patch runner — 2026-10-19

The PDO fixture reproduces the old receipt's array/SimpleXML parameter rejection.
The runner now parses scalar operation definitions, validates the pending plan
through the shared schema manager, and stores bounded JSON receipt metadata via
checked dbTable writes. It no longer records success merely because a column
exists, and exceptions reach CLI callers without redirecting or exiting zero.
The controller catches runner failures and uses its existing escaped failure UI;
server diagnostics retain the cause. Default-data failures also propagate and
are checked before registration advances the module version.

Receipts identify a module/version/XML operation position, include a SHA-256 of
the complete normalized operation and record pending/applied state. A per-module
database lock serializes writers. Completed operations can be skipped if later
registration fails. Changed operations or legacy ambiguous receipts require
review rather than blind replay. MySQL DDL cannot be made transactional: an
incomplete DDL receipt blocks automatic retry until an operator reviews the
schema. Data operations and their receipts use checked savepoints on InnoDB.
Existing receipts are retained, not rewritten. No arbitrary SQL or unbounded
row deletion is introduced; deleteRows binds values and validates identifiers.

Module Catalogue version: 3.142. The repair is four coordinated core files:
patch class, modulesadmin class (default-data error propagation), controller and
register.conf. Existing schema validation and installation hooks stay in place.
Source registration uses the normal Module Catalogue path.

Validation: the full catalogue regression suite, PHP lint and whitespace checks;
local PDO fixture using the real Discussion 3.036 XML against a random table;
scalar receipt regression, both columns widened, one receipt, business-row
preservation, repeat runs, registration failure/retry, parameterized apostrophe
handling, DML receipt-failure rollback, DDL failure with blocked retry, malformed
XML propagation and dependency results. Fixture tables/module rows/receipts are
removed in finally. Use only a disposable/local runtime:
`CHISIMBA_PATCH_TEST=1 php core_modules/modulecatalogue/tests/patch_runner_pdo_test.php`.

The original six-site rollout workaround remains historical evidence in
jquery-global-off-rollout-20261017.md. This repair does not automatically approve
or run unrelated pending schema updates. Six-site deployment receipts belong in
the dev-environment patch-runner-20261019 tool directory.
