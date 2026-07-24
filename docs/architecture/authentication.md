# Chisimba authentication architecture

## Status

This document defines the canonical authentication architecture for Chisimba 26.

The current LiveUser-backed login remains active only until the local-password
provider, session service, tests, and controller adapter have been completed and
verified. No new code may depend directly on LiveUser authentication.

## Dependency direction

```text
controller
   |
authentication service
   |
provider registry
   +-- local password provider
   +-- LDAP provider
   +-- future OIDC/SAML providers
   |
identity provisioning service
   |
MFA policy and challenge service
   |
authentication session service
```

Authorisation remains separate:

```text
userservice
groupservice
permissionservice
```

Authentication proves identity. It does not calculate groups, permissions,
roles, access levels, or administrator state.

## Provider rules

An authentication provider may:

- validate its own configuration;
- verify supplied primary credentials;
- return a provider-neutral identity result;
- return non-secret provider metadata.

An authentication provider must not:

- create, regenerate, or destroy Chisimba sessions;
- create or update local user records;
- assign groups, roles, permissions, or administrator status;
- redirect browser requests;
- render HTML or other user interfaces;
- store passwords, tokens, or other secrets in logs or results.

## Provisioning

Externally authenticated identities, such as LDAP identities, are mapped to a
local Chisimba user through a separate identity-provisioning service. Provider
verification and local account creation are distinct operations.

## MFA

MFA occurs after successful primary authentication and before the authenticated
session is established.

```text
primary provider succeeds
        |
MFA policy evaluation
        |
optional challenge
        |
session establishment
```

The initial architecture defines MFA contracts but does not force one MFA
implementation.

## Session rules

The session service is the only component that establishes authenticated state.
It must regenerate the session identifier before storing authenticated identity.
Authentication providers and controllers must not duplicate this work.

## Passwords

New and changed local passwords use PHP `password_hash()` formats. Legacy MD5,
SHA-1, or crypt-compatible values may be verified only for controlled migration
and must be replaced after successful verification. Plaintext and unknown
formats are rejected.

## Obsolete code

Duplicate contracts, abandoned scaffolds, LiveUser shadow-comparison machinery,
and retired provider implementations are quarantined under paths ending in
`_OBSOLETE`. They are unsupported and may later move to a dedicated archive
repository.

## Delivery sequence

1. Consolidate contracts and quarantine duplicates.
2. Implement the local-password provider.
3. Implement the authentication session service.
4. Add unit and integration tests.
5. Adapt login and logout controllers.
6. Remove LiveUser from the active authentication path.
7. Implement the general LDAP provider and provisioning adapter.
8. Return to native user, group, and permission-management UI work.
