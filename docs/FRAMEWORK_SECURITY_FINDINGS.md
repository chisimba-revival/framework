# Framework security findings register

Status: active review, 30 September 2026. The session-cookie flag repair is
deployed on KengaLearn; other findings record their deployment state below.

## Verified findings

| ID | Severity | Evidence | Recommended remediation |
| --- | --- | --- | --- |
| F-001 | High | KengaLearn's public home response sets `PHPSESSION` without `Secure`, `HttpOnly`, or `SameSite`. The active PHP configuration has `session.cookie_secure = Off`, `session.cookie_httponly = Off`, no SameSite value, and `session.use_strict_mode = Off`. | Configure the production session policy to use `Secure`, `HttpOnly`, `SameSite=Lax` (or stricter after login-flow testing), strict mode, cookies only, and an appropriate bounded lifetime. Test login, remembered login, logout, cross-site POST rejection, and HTTP-to-HTTPS handling before release. |
| F-002 | High | The production web container runs PHP 8.5.4. PHP published later 8.5 security releases. The public response includes `X-Powered-By: PHP/8.5.4`. | Rebuild the application image on the current supported PHP 8.5 patch release, run the framework/module test matrix, then deploy through the normal immutable-release workflow. Disable `expose_php`. |
| F-003 | Medium | Production config enables `display_errors` and `display_startup_errors`. The loaded Chisimba error shim intentionally suppresses legacy warnings but leaves fatal errors visible in HTTP responses. | In production, log errors without rendering details to clients; retain a generic error page with a correlation ID. Verify PHP, Apache and framework exception paths do not disclose file paths, SQL, stack traces or configuration values. |
| F-004 | Medium | The public home response did not emit CSP, HSTS, `X-Content-Type-Options`, frame-ancestor/X-Frame-Options, Referrer-Policy or Permissions-Policy headers. | Add headers at the reverse-proxy layer, starting with HSTS after confirming HTTPS-only operation, `nosniff`, a restrictive referrer policy and frame protection. Introduce CSP in report-only mode first because legacy inline scripts may need migration. |
| F-005 | Critical | The framework's only generic controller authorisation hook, `access::dispatchControl()`, had its decision-table check commented out and called `$module->dispatch()` directly. `access::isValid()` also unconditionally returns `TRUE`; `getPermissions()` has no callers. This bypassed controller `isValid()` methods. Verified affected controllers included `contextgroups`, whose mutations checked only POST, current-context and session-token values—not the actor's role—and administrator-only `contextpermissions`, which fell back to that permissive parent method for non-admins. | **Repair complete in source, not deployed.** The dispatcher now invokes an explicit controller `isValid()` policy for protected actions when one is defined, while retaining legacy login-only behaviour for controllers without that method. `contextgroups` independently requires the current user service's administrator/lecturer role for every mutation; `contextpermissions` uses an explicit current-user administrator check. The six explicit policies (`ai`, `communications`, `context`, `contextgroups`, `contextpermissions`, `cpd`) were inventoried; no additional bypass was found. This deliberately does not restore the incomplete legacy decision-table mechanism. **Local browser gate passed, 30 September:** anonymous, student, outsider, cross-course lecturer, same-course lecturer, administrator and live-revocation checks passed, including an allowed membership write and denied stale-form write. Public course viewing and Knowledge Map autosave/public-link/clipboard/read-only/revocation checks also passed. See the companion review for scope and fixture cleanup. Narrow production deployment and post-release verification remain pending. |
| F-006 | Critical | Historical observation: KengaLearn had `api` and `packages` installed and anonymously serving legacy XML-RPC user, file, content and remote-package operations. | **Contained on KengaLearn, 30 September 2026.** Following the Active Dynamic Mirroring removal, both legacy controllers are now retirement-only routes: all actions return HTTP 410 without initialising services. Removed unreachable remote-install/controller code. Default routes and GET/POST `serveapi` requests verified in production, focused behavioural tests pass, and Knowledge Map's complete module tree is unchanged. Backing XML/RPC classes and shared PEAR libraries remain pending caller inventory; this does not claim full dependency removal or deployment to other sites. |

## Review leads, not yet findings

| Lead | Evidence | Next evidence required |
| --- | --- | --- |
| Core cache deserialisation | KengaLearn has neither legacy Memcache PHP extension installed. The first-party Memcache adapter, activation setting and initialisers were removed in `63a5ed4ba`; generic cache fallback is APC/local only. | Remove the now-inert compatibility conditionals in a later isolated refactor; do not reintroduce PHP-object serialization for a shared cache. |
| Dynamic template inclusion | `controller::callTemplate()` includes a path from `_findTemplate()`. Request module names have slash removal but no strict registry allow-list; template names are normally controller-selected. | Trace all template-name inputs and replace permissive module resolution with a registered-module allow-list before relying on it as a traversal defence. |
| Legacy XML-RPC, BBCode and OpenID paths | Bundled and framework callers remain for XML/RPC, BBCodeParser and OpenID/MDB2 storage. | Establish route/module registration and production reachability; XML-RPC is subject to the Active Dynamic Mirroring investigation in F-006. |
| Legacy PEAR/MDB2 surface | Core startup still adds `lib/pear` to the include path and can initialise MDB2. LiveUser and obsolete LDAP adapters have been removed. | Produce the dependency/reachability map in the companion review plan before changing MDB2 bootstrapping or deleting further packages. |
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

1. Inventory remaining XML/RPC class and filter callers before deleting backing
   libraries; check other sites separately. KengaLearn's two legacy routes are retired.
2. Contain and repair F-005 before further feature deployment; then map all
   public entry points and module actions to authentication, authorisation and
   CSRF enforcement.
3. Verify native-auth and persistent-login journeys after the completed LiveUser
   source removal; do not restore the retired adapters.
4. Produce a machine-readable PEAR inventory: package, version/source,
   direct callers, production reachability and replacement/removal status.
5. Create small, independently tested remediation changes for F-001 through
   F-004; do not combine them with PEAR removal.
