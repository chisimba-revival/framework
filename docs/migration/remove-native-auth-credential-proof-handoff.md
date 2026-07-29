# Remove the temporary native-auth credential-proof handoff

This checklist is created with the MFA web-flow work so the V56 migration
handoff cannot become permanent compatibility code.

Remove the handoff only after every active password caller uses the canonical
authentication application boundary and the regression suite proves local and
configured external-provider login.

- Remove `credentialProof` and `getCredentialProof()` from
  `auth_database_class_inc.php`.
- Remove `verifiedProvider` and `getCredentialProof()` from
  `authenticate_class_inc.php`.
- Remove `getCredentialProof()` from `user_class_inc.php`.
- Remove the legacy `_record` assignment made solely for password-proof
  propagation.
- Remove static assertions and tests referring to the V56 handoff.
- Verify no controller calls `user::getCredentialProof()`.
- Verify password proof still creates no authenticated session, login-history
  entry, logged-in-users row, administrator marker, or remembered-login token.
- Verify ordinary login finalises once through the canonical session service.
- Verify MFA-required login finalises only after a valid, unexpired,
  single-consumption TOTP or recovery-code challenge.
- Verify invalid, expired, cancelled, and replayed challenges remain logged out.
