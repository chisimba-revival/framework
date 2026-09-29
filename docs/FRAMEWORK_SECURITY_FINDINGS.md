# Framework security findings register

Status: initial evidence pass, 29 September 2026. Findings are read-only;
none of the actions below have been applied to production.

## Verified findings

| ID | Severity | Evidence | Recommended remediation |
| --- | --- | --- | --- |
| F-001 | High | KengaLearn's public home response sets `PHPSESSION` without `Secure`, `HttpOnly`, or `SameSite`. The active PHP configuration has `session.cookie_secure = Off`, `session.cookie_httponly = Off`, no SameSite value, and `session.use_strict_mode = Off`. | Configure the production session policy to use `Secure`, `HttpOnly`, `SameSite=Lax` (or stricter after login-flow testing), strict mode, cookies only, and an appropriate bounded lifetime. Test login, remembered login, logout, cross-site POST rejection, and HTTP-to-HTTPS handling before release. |
| F-002 | High | The production web container runs PHP 8.5.4. PHP published later 8.5 security releases. The public response includes `X-Powered-By: PHP/8.5.4`. | Rebuild the application image on the current supported PHP 8.5 patch release, run the framework/module test matrix, then deploy through the normal immutable-release workflow. Disable `expose_php`. |
| F-003 | Medium | Production config enables `display_errors` and `display_startup_errors`. The loaded Chisimba error shim intentionally suppresses legacy warnings but leaves fatal errors visible in HTTP responses. | In production, log errors without rendering details to clients; retain a generic error page with a correlation ID. Verify PHP, Apache and framework exception paths do not disclose file paths, SQL, stack traces or configuration values. |
| F-004 | Medium | The public home response did not emit CSP, HSTS, `X-Content-Type-Options`, frame-ancestor/X-Frame-Options, Referrer-Policy or Permissions-Policy headers. | Add headers at the reverse-proxy layer, starting with HSTS after confirming HTTPS-only operation, `nosniff`, a restrictive referrer policy and frame protection. Introduce CSP in report-only mode first because legacy inline scripts may need migration. |

## Review leads, not yet findings

| Lead | Evidence | Next evidence required |
| --- | --- | --- |
| Core cache deserialisation | `dbTable` and the engine call `unserialize()` on values returned from configured Memcache/APC paths. | Confirm whether an untrusted actor can write cache entries, whether production uses those paths, and whether allowed-class restrictions or a JSON cache format are feasible. |
| Dynamic template inclusion | `controller::callTemplate()` includes a path from `_findTemplate()`. | Trace the module/template names from request routing and confirm strict registry validation prevents traversal or arbitrary local-file inclusion. |
| Legacy XML-RPC, BBCode and OpenID paths | Bundled and framework callers remain for XML/RPC, BBCodeParser and OpenID/MDB2 storage. | Establish route/module registration and production reachability; disable or retire unused public entry points before dependency removal. |
| Legacy PEAR/MDB2/LiveUser surface | Core startup still adds `lib/pear` to the include path and can initialise LiveUser/MDB2. | Produce the dependency/reachability map in the companion review plan before changing bootstrapping or deleting packages. |
| Broad runtime privileges | `allow_url_fopen` is enabled, `disable_functions` and `open_basedir` are unset. | Inventory actual stream-wrapper and process-launch use, then minimise the configuration without breaking supported functions. |

## Evidence notes

* Production observations were taken from the KengaLearn web container and
  public HTTPS response headers. No secrets, user data or session values were
  captured.
* The PHP/session findings describe effective production configuration, not
  merely repository defaults.
* Severity will be revisited after testing deployment topology, cookie domain
  redirects, authentication flows and error-page behaviour.

## Next review tranche

1. Map all public entry points and module actions to authentication,
   authorisation and CSRF enforcement.
2. Trace native-auth, persistent-login and legacy LiveUser callers.
3. Produce a machine-readable PEAR inventory: package, version/source,
   direct callers, production reachability and replacement/removal status.
4. Create small, independently tested remediation changes for F-001 through
   F-004; do not combine them with PEAR removal.
