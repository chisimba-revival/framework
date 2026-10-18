# Legacy Login backend retirement — 17 October 2026

Explicitly authorised by Derek after the preceding client cleanup and separate
reference/security checks. Login 0.083 removes four unused classes: nonce, dbnonce,
captcha and loginsecurity, plus the tbl_login_nonce source schema and its catalogue
TABLE declaration. No database table or row is dropped or migrated. Current native
Security authentication and the separate Utilities CAPTCHA are retained.

Current application-source searches found no external callers of the deleted
classes/table. Historical migration inventories remain historical. Ordinary old
Login visits redirect to Security; old fragment routes render the native form;
retired generatecaptcha/verifycaptcha routes return 410 without session changes.
The compatibility module needs no new table. Do not uninstall the old module to
perform this update: catalogue uninstall is a separate destructive operation.

## Separate validation

* retired_login_backend_test.php: removed paths/schema declaration, retained
  Utilities CAPTCHA, GET/POST retired routes return 410 without loading services
  or mutating session state.
* Native login CSRF, MFA-before-session, authentication bypass, guarded login,
  cancellation/expiry, authentication/session services and P336 abuse contract pass.
* Disposable PHP 8.5/PDO clone with outbound networking/workers absent and global
  jQuery suppressed: fresh and repeat catalogue registration preserve an existing
  nonce table and its rows exactly, including a synthetic sentinel.
* Live HTTP: retired GET/POST endpoints return empty 410; all three legacy fragment
  routes contain native CSRF/abuse fields and no old CAPTCHA client.
* Chrome: old Login URL redirects to maintained sign-in; wrong-password feedback,
  retained username/cleared password, correct sign-in and sign-out work. No PHP
  warnings/deprecations/fatals observed in the exercised container routes.

The runtime preservation check is tests/dependencies/login_backend_preservation_runtime.php.
It is CLI-only and refuses any DSN except the isolated editor-db/editor_fixture
clone. It adds a synthetic nonce row, then uses normal catalogue install/update;
run only in that disposable environment and destroy the clone afterwards.

The real local Login module remains dormant; source is updated without enabling
it. Production is unchanged. Global jQuery loading is still enabled in real sites.
