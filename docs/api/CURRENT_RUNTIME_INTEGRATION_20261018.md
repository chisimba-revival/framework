# REST API integration with the current runtime — 18 October 2026

This integrates Prince Mbekwa's `feature/rest-api-v1` at `b65615ae6` with
`release/consolidation-20260930` at `449f3ae09`. It is one shared API, proposed
back to the API branch, not an alternative API implementation. No production
installation or deployment is performed by this change.

## Contract retained

The `/api/v1` routes, legacy login aliases, JWKS alias, response envelopes,
access/refresh lifetimes, device identifiers, RSA signing and field-service
proxy payloads remain. Field role names and scope vocabulary are unchanged.
The field service and Redis denylist integration are retained. A first text-only SimpleBlog create endpoint has subsequently been added; the
mobile client remains subsequent work. Existing routes retain their contracts.

## Runtime and security changes

- Keep the current PDO-only database, PEAR removal, scoped browser compatibility
  and session implementation when reconciling the older branch.
- Token persistence, module listing, canonical identity/user/permission lookups,
  ancillary analytics and schema helpers use `dbTable` prepared operations.
- Resolve logical user IDs through the canonical permission identity service;
  join memberships by the logical group ID, not an unrelated storage key.
- Refresh rotation locks the credential row and uses a checked transaction or
  savepoint. A replacement failure preserves the original token. A replay still
  revokes its device family, as Prince's contract specifies. Clients must not
  blindly retry refresh after losing its response; reauthentication may be needed.
- Epoch/fingerprint updates roll back if denylist publication fails, allowing a
  subsequent retry to publish. Redis and SQL are not a distributed transaction:
  a SQL commit failure after successful Redis publication can over-revoke and
  requires operational reconciliation. This change does not claim otherwise.
- REST API 1.001 adds a repeatable Catalogue postinstall hook. Registered tables
  remain owned by restapi, are required to be InnoDB, and the epoch's unique
  user key is enforced. Duplicate existing epochs fail the upgrade rather than
  being silently discarded. Numeric user-ID columns can be widened explicitly.
- API key identifiers remain numeric; framework-created tables populate the
  public id from the generated storage key when it is absent.
- Signing keys require an explicit path outside the application/web root in an
  existing private directory. First-use creation is serialised and publishes
  a complete owner-readable/writable file. No automatic source-tree fallback.
- Unexpected REST errors no longer log exception traces containing credentials.

## Upgrade prerequisites

Keep the existing private key bytes. If a development installation used the old
`app/var/restapi/jwks_private.pem` fallback, move that same key to a private,
non-web-served directory and set `CHISIMBA_JWKS_KEY_PATH` or
`KEWL_JWKS_KEY_PATH` to it before enabling the API. Do not generate a replacement
merely to change its location. A pre-provisioned read-only key remains supported.
The directory must be writable only when first-use key generation is needed.
Keep the existing Redis and field-service settings.

Run normal Module Catalogue registration/update so the postinstall hook runs.
The explicit CLI `restapi/scripts/install-schema.php` calls the same upgrade
service. Both fresh and repeated upgrades are tested on disposable data.
Existing duplicate epochs or nontransactional tables require investigation;
this migration intentionally does not choose which records to delete.

## Validation

`python3 tests/database/run-restapi-checks.py` creates an internal Docker network
and disposable MariaDB with tmpfs data, mounts only source, and removes resources
on exit. It uses no installed-site credentials or data. Its behavioural fixture
uses real dbTable, schema, user, identity, permission, token, signing-key and REST
authentication/permission guard code. Framework object composition is replaced
by a fixture service registry; browser session priming is omitted. Redis
publication uses an injected failure-capable test publisher.

Passing checks include schema repeatability, distinct logical/storage identities,
field token claims, wrong token type, live permission grants/revocation,
deactivated accounts, refresh rotation, replay-family revocation, wrong device,
replacement rollback, concurrent refresh, API-key ownership, epoch publication
retry, transaction ownership, private-key location and concurrent provisioning.
The runner also checks real engine/database composition and registered installer
schema creation. PEAR/MDB2 must remain unloaded.

The portable source contract suites pass 29 routing and 22 token checks. Existing
focused checks pass for guarded login, CSRF, no session before MFA, composition,
canonical permissions, session handling, form lifetime, password verification,
MFA crypto, remembered login, abuse protection and Catalogue dependency updates.

An existing `mfa_challenge_contract_test.php` fails because its MemoryMfaRepository
omits five interface methods. The identical failure is reproduced on the unchanged
release checkout; it is not repaired as part of this integration.

The existing local PHP 8.5 installation subsequently passed an HTTPS journey:
login evidence/cookies, password login, bearer-only identity, anonymous and
cookie-only API refusal, unprivileged resource/upload refusal, refresh rotation
and replay-family revocation, granted file listing and PNG upload, immediate
permission removal, and deactivated-account refusal. Temporary accounts, grants,
token records and uploaded files were removed. The existing database was backed
up before additive schema changes; the normal home page still returned 200.

Installed testing found a missing MODULE_DESCRIPTION in REST registration and
PHP 8.5 deprecations in the shared upload path. Registration now supplies its
description; upload uses date() for numeric timestamps, allows finfo objects to
be released normally, and declares the quota/file collaborators. The repeated
HTTP journey passed without new PHP diagnostics.

Still required before merge/release acceptance: Prince's real field-client/service
end-to-end check with its Redis/PostGIS environment. Local Redis and upload checks
do not establish that result. No production rollout is authorised here.


## Text-only SimpleBlog prototype

`POST /api/v1/simpleblog/posts` uses SimpleBlog's existing publishing policy and
service. It accepts only title, plain-text content and draft/posted status;
owner and personal scope come from the authenticated user. It introduces no
SimpleBlog storage or permission changes. The API README specifies input limits,
responses and recovery. Media and unknown fields are rejected; HTML is escaped
before the shared rich-text storage boundary. Invalid scalar JSON now reaches
request validation rather than failing a typed-property assignment.

Installed HTTP checks passed for denied and revoked publishers, exact routing,
invalid ownership/media/status/body, draft and published creation, stored scope,
literal markup, and anonymous draft/public visibility. Synthetic posts and test
identities were removed. Text input checks plus existing routing/token contracts
pass. This is a create-only test endpoint; idempotent offline retry and a mobile
client are not included. No production deployment occurred.
