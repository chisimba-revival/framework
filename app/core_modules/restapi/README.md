# Chisimba REST API

Versioned JSON API exposed under `/api/v1`.

For the current PDO-only runtime, upgrade prerequisites and validation limits,
read [the 18 October integration record](../../../docs/api/CURRENT_RUNTIME_INTEGRATION_20261018.md).
Signing keys now require an explicit private path outside the web root; preserve
the existing key when upgrading.

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
| 422 | `VALIDATION_ERROR` | Evidence token not bound to this session | Fetch `GET /auth/login` again, keeping the session cookie, then post |
| 422 | `VALIDATION_ERROR` | Honeypot `website` populated | Remove the field; a real browser never fills it |
| 429 | `RATE_LIMITED` | Too many failures for this subject | Wait `details.retryAfter` seconds |

A 422 names the offending field in `details`, so the 422 cases stay
distinguishable: `details.abuse_evidence` for expired or missing evidence,
`details.session_expired` for evidence that was not carried back with its
session cookie, and `details.website` for the honeypot.

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

`GET /species` and `GET /species/{code}` add **no right**. `requireAuth()`
alone guards them, because the catalogue is reference data that every caller
may read, and because a right is not a small thing here: an undefined right
denies *everyone* (`rightIdForArea()` returns null and `can()` fails closed), so
a `species.view` guarded route would answer `403` to every caller until an
administrator re-ran `configure-service.php`. The catalogue is advisory and
carries nothing sensitive — it holds no counts, locations or sightings — so
authentication is the check that fits it. Scope enforcement stays where the
contract puts it: the field service independently requires `field:write` on the
forwarded token.

`POST /sync/push` and `POST /sync/pull` add **no right** either, and the same
reasoning applies with more force. They are the write path: a device pushes its
queued observations and pulls the changes made elsewhere. Adding a right would
not make them safer in any useful way, because the field service is the thing
that decides whether a caller may write — Chisimba's guard here is that no
anonymous request reaches it at all. Requiring a local right would mean an
undefined one blocking every push until `configure-service.php` was re-run,
which is a queue that drains at no time rather than one that is gated.

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
| `GET /species` | 200 | Proxied from the field service; `?q=` searches it |
| `GET /species/{code}` | 200 | Proxied from the field service; 404 for a code it does not hold |
| `GET /outings` | 200 | Proxied from the field service |
| `GET /competencies` | 200 | Proxied from the field service |
| `POST /sync/push` | 200 | Proxied to the field service; answers per operation, never in bulk |
| `POST /sync/pull` | 200 | Proxied to the field service; one page of changes |

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

## Species resource

The species catalogue is **not stored here**. It belongs with the field data in
the field guiding service, which is where it is edited and where the offline
bundle a device builds comes from. These two routes proxy it, so a device that
already talks to Chisimba can build its catalogue without a second base URL and
a second credential:

```bash
curl -H "Authorization: Bearer $TOKEN" \
     http://localhost:8080/api/v1/species
curl -H "Authorization: Bearer $TOKEN" \
     http://localhost:8080/api/v1/species/LEOP
```

The caller's own `Authorization` header is forwarded verbatim. No credential is
minted here, and none is held here: the field service still validates the
signature, the revocation epoch and the `field:write` scope on its own side, so
the answer is for the person who asked — the same as every other route.

A proxy that cannot reach the catalogue must say so without guessing. Three
failures are distinct on purpose:

| Failure | Status | `error.code` |
| --- | --- | --- |
| The field service could not be reached at all | 503 | `UPSTREAM_UNAVAILABLE` |
| It answered something that is not JSON | 502 | `UPSTREAM_UNREADABLE` |
| It refused the request or does not hold the code | the status it answered | the code it answered, or `UPSTREAM_ERROR` when it sent none |

An upstream refusal is passed through as its own status and code, so a 404 for
an unknown code is a 404 that says `unknown_species` rather than a generic
message here. Only `UPSTREAM_UNAVAILABLE`, `UPSTREAM_UNREADABLE` and the
`UPSTREAM_ERROR` fallback for a refusal that carried no code are this module's
own codes, and all three describe the journey rather than the catalogue — a
fault names tables, columns and constraints, which is a schema handed to
whoever can read the response.

`?q=` is passed through to the catalogue, which searches code, common name,
scientific name and description. The catalogue is never paginated here: it is
reference data of a known, bounded size, and a device building an offline bundle
needs all of it. `species` is always an array, never `null`, so a device can
tell an empty catalogue from an unexpected response.

The upstream base URL defaults to `http://field-service:8080`, which is the
service name on the Docker network. Override it with the
`CHISIMBA_FIELD_SERVICE_URL` environment variable or the
`KEWL_FIELD_SERVICE_URL` constant in `config/restapi_inc.php`
(see `config/restapi_inc.php.example`), as `scheme://host:port` with no
trailing slash.

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
## Initial text-only SimpleBlog posting

`POST /api/v1/simpleblog/posts` accepts an access token in the standard
`Authorization: Bearer` header. SimpleBlog must be installed. Its existing
personal-publishing policy applies: signing in alone does not grant publishing.
The endpoint creates a post in the authenticated user's personal blog; it does
not accept a user ID, site/course scope or an existing post ID. API keys are not
supported for this initial endpoint.

Send `Content-Type: application/json` with:

```json
{
  "title": "Notes from the field",
  "content": "First observation.\nSecond observation.",
  "status": "draft"
}
```

`title` is required (1–250 characters after trimming). `content` is required,
nonblank UTF-8 plain text (at most 50,000 bytes). Newlines are preserved. HTML,
including image tags, is displayed literally. Images, files, media URLs, content
blocks, tags, ownership fields and other additional fields are rejected.
Multipart and form submissions are not supported.

Status defaults to `draft`. Use `posted` explicitly to publish immediately.
A successful response is HTTP 201 with `data.id` and `data.status`. A draft can
be opened by its authorised owner in the normal SimpleBlog management interface;
it is not publicly readable. A published personal post uses SimpleBlog's normal
public reading policy. Editing and deleting remain in the web interface for now.

HTTP 401 means authentication is missing or no longer valid; retain the typed
text and sign in again. HTTP 403 means the user cannot publish; retain the text
and ask the site administrator about publishing access. HTTP 422 means the
request content is invalid or contains unsupported fields. A missing SimpleBlog
installation returns 404. No rejected request should create a post.

This first endpoint is create-only and has no idempotency contract. If a network
connection fails after submission, the outcome may be uncertain: retain the
local draft and check SimpleBlog before submitting again. An offline mobile
queue must not automatically retry this write until duplicate-safe submission
is added. No mobile app or automatic offline sync is introduced by this endpoint.

Installed local HTTPS tests cover bearer login, missing/revoked permission,
wrong method/route, rejected media/ownership fields, draft and published saves,
actual persisted owner/scope, escaped markup and anonymous draft/public access.
`tests/textpostinput_test.php` covers the text boundary and size/type limits.

### Public site branding

`GET /api/v1/site/branding` is available before authentication. It returns
`data.version` (1), the configured `siteName`, site-wide `canvas`, `colours`
(`primary`/`accent`, validated six-digit hex or null) and optional `logo`
(`mime` and `base64`). The shared canvas `sitebranding` service reads the
canonical configuration and canvas.json; user/context overrides do not apply.
Only a declared raster logo inside the selected skin is returned: PNG, JPEG or
WebP, at most 192 KiB and 2048 pixels on either axis. Remote URLs, escaping
symlinks, SVG and filesystem paths are never exposed. Missing/unsupported
assets yield null; clients retain their normal layout and accessible defaults.

Clients may cache this public identity separately per canonical HTTPS site URL.
Render the name as text, accept only the documented colour/image formats, and
show the actual connected server address alongside branding. A logo is not proof
of server identity. This endpoint neither authenticates nor grants permissions.
