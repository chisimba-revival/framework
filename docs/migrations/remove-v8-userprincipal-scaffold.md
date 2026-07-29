# Remove V8 serialized userprincipal scaffold

Owner: security module

Remove this scaffold only after the canonical identity/user caller migration.

- Remove `storeNativeCompatibilityPrincipal()` from `auth_database`.
- Remove its single call and the `Temporary caller-migration scaffold` marker.
- Prove no source consumer reads the `userprincipal` session key.
- Prove login, logout, anonymous access, administrator access, and session
  fixation tests pass using `NativeSessionService` only.
- Remove this checklist and `remove-v8-userprincipal-scaffold.sh`.

No compatibility code survives a completed migration unless it provides
ongoing architectural value.
