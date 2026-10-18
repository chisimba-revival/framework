# Native authentication scaffold

This directory contains **non-active** Milestone 8 contracts and a service skeleton.

It does not replace LiveUser, alter service registration, change login routing, or
modify the current session. Its purpose is to define a testable boundary around
a future Chisimba-owned authentication and authorisation implementation.

Activation is prohibited until:

1. current LiveUser behaviour has been captured by comparison tests;
2. repository adapters have been implemented against the confirmed schema;
3. password compatibility and migration have been independently reviewed;
4. session, logout, timeout, and privilege behaviour match the legacy path;
5. a default-off feature flag and immediate rollback path exist.

## Authenticated form lifetime (Security 3.112)

The production composition supplies `CsrfTokenService` with the canonical login
identity and a random authentication epoch. `issue()` and `issueForSession()` then
return context-separated HMAC tokens derived from one session-held secret. These
tokens have no independent clock expiry, are reusable across controls/retries,
and are not evicted by other tabs or page renders. Storage remains constant.
`consume()` retains its compatibility name but validates origin for these tokens;
it is not a transaction-idempotency mechanism. Owning services must enforce
revision checks, unique operation keys and payment reconciliation as appropriate.

Logout and every new authentication establishment clear token state and replace
the epoch, including logging in as the same account in the same second. Routine
PHP session-ID regeneration preserves the login epoch. Authentication and resource
permissions are still checked by each caller. Lost PHP session storage or revoked
login credentials are not made valid by this change. Remembered-login restoration
establishes a new login and cannot authorise a form from the destroyed session.

Prelogin/anonymous tokens and explicitly constructed legacy services retain their
expiring, single-use contract. Old 64-character tokens already rendered before
this update use the old validation path until the form is renewed; no unknown or
expired token is silently accepted. Preserve entered values on that transition.

Regression: `php tests/nativeauth/authenticated_form_lifetime_test.php` covers a
simulated year, 1,000 renders, retry, forged/context/session-isolated tokens,
logout, same-user relogin and account changes. The full `tests/nativeauth` PHP
suite passes (44 tests), including on an isolated committed-source snapshot with
only the form-lifetime changes applied. Chrome also saved an unchanged synthetic Events form
after 15 page renders in a different tab. This is a local source/runtime update;
production sites require coordinated framework deployment.
