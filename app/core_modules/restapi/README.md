# Chisimba REST API

Versioned JSON API exposed under `/api/v1`.

This module is a thin translation layer. It owns HTTP concerns only:
routing, authentication hand-off, request validation, pagination and the
JSON envelope. Every domain rule belongs to the canonical Chisimba services,
and this module must not reimplement any of them.

## Architecture

Requests are intercepted by the engine (`engine_class_inc.php`,
`_dispatchRestApi()`) and dispatched into this module, which extends
`RestController` (`classes/restcontroller_class_inc.php`) for routing,
`requireAuth()`, `requirePermission()`, validation and the response envelope.

Controllers handle request handling; services own domain rules; repositories
own persistence. In particular:

- **Authentication** is delegated wholesale to the canonical composition
  root `nativeauthwebcomposition` (`security/classes/nativeauthwebcomposition_class_inc.php`),
  which wires `guarded_login` → `auth_database` → `LocalPasswordProvider` →
  `NativePasswordVerifier`. Passwords are never read, compared or hashed here.
- **Users** are owned by `userservice`, provisioned through
  `userprovisioningservice`, and mapped to permission identities by
  `identityservice`.
- **Authorisation** uses `permissionservice::areaIdForName()` →
  `rightIdForArea()` → `isGranted()`. There is no `accesslevel` shortcut and
  no hand-written rights SQL. Any null or error denies.
- **Files** are owned by `filemanager`'s `fileapi`.

No SQL is written directly against `tbl_users`, the permission tables or the
token tables. Doing so bypasses the canonical guards and is forbidden by
`framework/AGENTS.md`.

## Authentication

### Two-step login

`GET /api/v1/auth/login` is unauthenticated and issues the material a login
submission must carry:

```json
{
  "csrf_token": "...",
  "issued_at": 1791061502,
  "nonce": "...",
  "signature": "...",
  "minimum_seconds": 1,
  "maximum_seconds": 3600
}
```

`POST /api/v1/auth/login` then submits those values with the credentials:

```json
{
  "username": "admin",
  "password": "...",
  "csrf_token": "...",
  "abuse_issued_at": 1791061502,
  "abuse_nonce": "...",
  "abuse_signature": "..."
}
```

Every field is enforced by the canonical stack, not by this module:

- `csrf_token` is consumed by `AuthenticationApplicationService::begin()`
  (`CSRF_BEGIN` context `native_auth_begin`).
- `abuse_issued_at` / `abuse_nonce` / `abuse_signature` are the signed
  evidence required by `AbuseProtectionService::evaluate()`. The signature is
  HMAC'd with the installation master key, so a client cannot mint it.
- The optional honeypot field `website` must be absent or empty.
- `minimum_seconds` is a minimum dwell time: submitting faster is rejected,
  so clients must wait at least that long between the two calls.

The username is resolved by the canonical provider, which accepts a **username
only**. Email is deliberately not a login identifier.

### Login failures are distinguishable

`POST /auth/login` can fail in several ways, and the response says which. The
abuse verdict is evaluated *before* any credential is inspected, so none of
these responses can reveal whether an account exists or whether a password was
wrong.

| Status | `error.code` | Cause | Client action |
| --- | --- | --- | --- |
| 401 | `UNAUTHORIZED` | Credentials rejected | Do not retry with the same password |
| 422 | `VALIDATION_ERROR` | Evidence invalid or expired | Fetch `GET /auth/login` again and wait `minimum_seconds` |
| 422 | `VALIDATION_ERROR` | Honeypot `website` populated | Remove the field; a real browser never fills it |
| 429 | `RATE_LIMITED` | Too many failures for this subject | Wait `details.retryAfter` seconds |

A 422 names the offending field in `details`, so the two 422 cases stay
distinguishable: `details.abuse_evidence` for expired or missing evidence,
`details.website` for the honeypot.

Two consequences for clients:

- **Do not report a 422 as a failed sign-in.** It is a protocol error in the
  request, not a rejected credential. An earlier revision of this module
  returned `Invalid credentials` for all three conditions, which made a client
  that submitted too quickly believe the password was wrong.
- **Honour `details.retryAfter` on 429.** The value is computed by
  `AbuseProtectionService` from the current failure count and the configured
  window, so it grows rather than repeating a fixed value. It is also sent as
  the `Retry-After` response header.

### Sessions

The PHP session is an **internal implementation detail**. Clients authenticate
with a bearer token and never manage a session cookie after login.

`requireAuth()` validates the bearer token, then calls `primeSessionUser()`,
which writes the validated token's user id into the server-side session so the
canonical services (`fileapi`, `permissionservice`) can resolve identity. The
session is therefore populated *from the already-validated token*, never from
anything the client submits.

Consequences for clients:

- The session cookie must be carried between `GET /auth/login` and
  `POST /auth/login`, because the CSRF token is session-bound. This is the only
  point at which the cookie matters.
- **No cookie is required on any other request.** `GET /files?policy=image`
  returns 200 with the bearer token alone and no cookie. The session exists
  server-side; the client does not participate in it.
- A cookie captured under one token pair is not usable after that pair is
  revoked: logout destroys the session and refresh rotates the session
  identifier.

The user context is always derived from the server-side session, which in turn
is derived from a validated token. No handler accepts a user identifier from
the request, which is what keeps "never trust submitted scope/owner identifiers"
true for this API.

### Presenting credentials

Accepted, and only these:

| Credential | Header |
| --- | --- |
| Access token | `Authorization: Bearer <token>` |
| API key | `X-API-Key: chk_…` |

Tokens and keys are **not** accepted from the query string, and there is no
supported alternative header for them. `TokenAuthService::validateFromRequest()`
is always given the request *headers*, never `$_GET`, so `?access_token=` does
not authenticate. This keeps credentials out of web-server access logs,
browser history and `Referer` headers.

MFA statuses (`mfa_required`, `mfa_enrolment_required`) fail closed: this API
issues stateless bearer tokens, so anything other than a completed login
returns `401 Invalid credentials` rather than partial access.

## Configuration

Two signing secrets must be supplied through the environment or token
operations throw:

| Variable | Purpose |
| --- | --- |
| `CHISIMBA_JWT_SECRET` | HMAC key for JWT signing |
| `CHISIMBA_SITE_KEY` | Site signing key |

`TokenAuthService::resolveSecret()` reads the environment and nothing else,
and throws when a value is absent. There is deliberately no default in source:
a committed secret is not a secret, and a shared development default would
mean every installation signs tokens with a publicly known key. For local
development, copy `.env.example` to `.env` and replace the values;
`docker-compose.yml` requires both variables and fails fast when they are
missing.

`restapi/register.conf` also declares `CONFIG: jwt_secret`, reserved for
reading the value from module configuration once the token service has a
verified handle to it.

## Permissions

Authorisation is expressed through the permission area `restapi` under the
`chisimba` application. The ten rights are seeded idempotently by
`scripts/configure-service.php` and each is checked with `requirePermission()`
(or an explicit self-or-permission check) before any handler runs.

| Right | Guards |
| --- | --- |
| `users.view` | `GET /users`, and reading another user's record |
| `users.create` | `POST /users` |
| `users.edit` | `PATCH /users/{id}`, editing another user |
| `users.delete` | `DELETE /users/{id}` |
| `modules.view` | `GET /modules`, `GET /modules/{id}` |
| `modules.install` | `POST /modules/{id}/install` |
| `modules.uninstall` | `POST /modules/{id}/uninstall` |
| `files.view` | `GET /files` |
| `files.upload` | `POST /files` |
| `files.delete` | reserved; no endpoint currently uses it |

`GET /users/{id}` and `PATCH /users/{id}` allow a caller to read or edit their
own record without holding `users.view` / `users.edit`, and deny access to any
other user's record. That is a deliberate self-service rule, not a missing
check. `is_active` is excluded from self-service: it requires `users.edit`
regardless of whose record is being edited.

Verified with a zero-grant account (a permission identity with no rights):
`403` on every protected resource, `200` on self-service reads. The same
requests succeed for an administrator, confirming the decision comes from the
permission layer rather than a blanket rule.

## Endpoints

Statuses below were observed as an administrator.

| Endpoint | Status | Notes |
| --- | --- | --- |
| `GET /auth/login` | 200 | Issues CSRF token and abuse evidence |
| `POST /auth/login` | 201 | Returns access and refresh tokens; also 401, 422 or 429 — see below |
| `POST /auth/refresh` | 200 | Rotates the refresh token, revoking the old one |
| `POST /auth/logout` | 200 | Revokes the refresh token; the access token remains valid until it expires |
| `GET /auth/me` | 200 | Current identity |
| `GET /users` | 200 | Paginated |
| `POST /users` | 201 | Returns the created user |
| `GET /users/{id}` | 200 | Self, or with `users.view` |
| `PATCH /users/{id}` | 200 | Self, or with `users.edit` |
| `DELETE /users/{id}` | 204 | Guarded canonical rollback, not an unconditional delete |
| `GET /modules` | 200 | |
| `GET /modules/{id}` | 200 | |
| `POST /modules/{id}/install` | 409 | Expected for an already-installed module |
| `POST /modules/{id}/uninstall` | 200 | Refuses mandatory core modules |
| `GET /files?policy=…` | 200 | Bearer token only; no session cookie needed |
| `POST /files` | 201 | Bearer token only; no session cookie needed |

`DELETE /users/{id}` is deliberately not a general delete. It runs the
canonical guarded rollback (`identityservice::rollbackProvisionedIdentity()`
then `userservice::rollbackProvisionedUser()`), which only succeeds when the
permission identity and the `tbl_users` row still match the identifiers
captured at provisioning. Anything else returns `409` rather than deleting a
live account.

Two limits worth stating plainly. This is **not** full offboarding: it does not
remove API keys, MFA enrolments or persistent-login rows, and it does not check
whether the account has ever signed in. It revokes the account's refresh tokens
*before* removing anything, so a held refresh token cannot keep minting access
tokens for a deleted account — without that, the rotation loop would issue
fresh tokens indefinitely. Real offboarding belongs in the canonical security
services, not here.

`PATCH /users/{id}` treats `is_active` as administrative rather than profile
data: changing it requires `users.edit`, so a user editing their own record
cannot deactivate (or reactivate) their own account. Other profile fields are
self-service.

## Files resource

`fileapi` accepts a **file-type policy** rather than a user id, and returns a
**flat** payload — `files` and `folders` are siblings of `folder`, not nested
under a `data` key. Only these six policy names are accepted; anything else is
rejected with `422` before `fileapi` is called:

`image`, `audio`, `video`, `pdf`, `assignment`, `zip`

```bash
curl -H "Authorization: Bearer $TOKEN" \
     -F "policy=image" -F "file=@photo.png;type=image/png" \
     http://localhost:8080/api/v1/files
```

`policy` must be sent in the **form body**. `parseRequestData()` merges
`$_GET` into the request data only for GET, DELETE, HEAD and OPTIONS, so a
query-string parameter is not visible to a POST handler. That precedence is
intentional: merging them would let a query parameter override a body field.

`fileapi` validates the upload against the policy's extension and mimetype
allowlists using `finfo`. A file that merely *claims* to be an image is
correctly rejected with `invalid_mimetype`.

### Removed endpoints

`GET /files/{id}` and `DELETE /files/{id}` are **not provided**.
`fileapi` exposes no scoped equivalents, and the underlying `dbFile`
`getFileInfo()` and `deleteFile()` take a bare file id with no ownership
check. Exposing them through this API would let any authenticated user read
or delete any file by guessing its id.

### Error reporting

Errors from `fileapi` carry a stable machine-readable `error.code`
(`picker_root_not_found`, `upload_forbidden`, `invalid_mimetype`, and so on).
That code is surfaced in the REST response as `details.fileApiCode`. The
`error.message` from `fileapi` is deliberately **not** used, because it is
localised through the language service and an incomplete language pack renders
it as an unhelpful `error_languageitemmissing` string that hides the real
cause.

## Installation

This module owns two tables, declared in `register.conf` and defined in `sql/`:

- `tbl_rest_refresh_tokens` — refresh tokens with rotation and revocation
- `tbl_rest_api_keys` — long-lived API keys

Both use `user_id VARCHAR(25)`, matching `tbl_users.userid`. An earlier
version declared these as `INT`, which silently limited the API to accounts
whose identifier happened to be numeric; under `STRICT_TRANS_TABLES` a
non-numeric identifier raises `Incorrect integer value` and the login fails
with an opaque database error.

The tables are **not** created at runtime. `TokenAuthService` used to issue
`CREATE TABLE IF NOT EXISTS` from its constructor, which put DDL on every page
request and hid the wrong column type. Apply them through the guarded,
repeatable installer script instead:

```bash
php core_modules/restapi/scripts/install-schema.php /var/www/html
```

The script creates each table from this module's own `sql/` definitions when
absent, and widens an existing `user_id` column only when it is numeric. It
never drops or rewrites data, and prints what it did on each run.

Rights are seeded separately and idempotently:

```bash
php core_modules/restapi/scripts/configure-service.php /var/www/html
```

A fresh installation also needs a filemanager user root before the files
resource can resolve a picker location:

```bash
php core_modules/filemanager/scripts/provision-user-folders.php /var/www/html
```

`fileapi::pickerLocations()` only offers a `user` location when a folder
record exists at `users/<userid>`, and that record is otherwise created
lazily on a user's first upload. An installation whose accounts have never
uploaded therefore reports `picker_root_not_found` even for a legitimate
administrator.

## Resolved since first writing

These were listed as open and have now been addressed:

- `register.conf` now carries a `RULE` entry, so the ten REST rights are seeded
  by the normal Module Catalogue registration path as well as by
  `scripts/configure-service.php`.
- The context module's tables are created by `context/scripts/install-schema.php`
  from the module's own `sql/*.sql` definitions, so the per-request
  `MDB2 Error: no such table | SQL: ALTER TABLE tbl_context …` is gone. The
  runtime `ALTER TABLE` in `dbcontext::init()` — which put DDL on ordinary page
  requests, contrary to `framework/AGENTS.md` — was removed at the same time;
  `tbl_context.sql` already declares `use_sections`.
- All the PHP 8.5 warnings are fixed at their cause, not suppressed: null
  arguments to `trim()`/`strtolower()`/`strtoupper()`, the implicit-required-
  parameter deprecation in `dbHandlermanager::dumpDatabaseToFile()`, the
  dynamic-property deprecation in `DbHandler`, the deprecated backticks in the
  bundled getID3, and `mkdir(): Permission denied` in the files module.
- The stray `security/classes/initialadminprovisioningservice_class_inc.php.orig`
  was removed.
- `filemanager/classes/foldercheck_class_inc.php` was deleted. It was a
  near-duplicate of `userfoldercheck::checkUserFolder()`, with no caller and no
  registration entry, and its comments referred to a pre-MDB2 persistence API.
- `userservice` no longer builds SQL by escaping values in PHP. `listUsers()`,
  `findOne()` and `valueAvailable()` bind their values as `?` parameters through
  `prepare()`/`execute()`. The previous `quoteValue()` used `addslashes()`, which
  is only safe because the connection charset is `utf8mb3`; it would break under
  a charset that admits a backslash as a trailing multi-byte byte.
- Credential smuggling through non-standard headers is gone. Only
  `Authorization: Bearer` and `X-API-Key` are honoured, and header names are
  matched case-insensitively, so `AUTHORIZATION` works as RFC 7230 requires.
- Login failures now report their actual cause (see *Login failures are
  distinguishable*) instead of collapsing into `Invalid credentials`.

## Still open

- User-root provisioning is lazy on upload rather than wired into user creation,
  so a brand-new account has no file-manager folder until its first upload. Run
  `filemanager/scripts/provision-user-folders.php` after bulk-provisioning users.
- The bundled getID3 library still contains PHP 4 constructs such as `eregi`.
  Only the backticks were touched. Modernising vendored third-party code is a
  separate piece of work.
- `installer/dbhandlers/dbhandler.inc` is now LF-only; it was originally CRLF.
  Its content is correct, but the change will show as a whole-file whitespace
  diff rather than a one-line addition.

## A note on verifying warnings

`/tmp/chisimba-php85-error.log` is append-only, so a `tail` of it shows
historical entries from before a fix and will make a resolved warning look
unresolved. Rotate the log first, exercise the endpoints, then read it:

```
docker-compose exec -T web sh -c 'mv /tmp/chisimba-php85-error.log /tmp/old.log; : > /tmp/chisimba-php85-error.log; chown www-data:www-data /tmp/chisimba-php85-error.log'
```