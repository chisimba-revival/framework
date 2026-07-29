# Authentication country and installer V22 checklist

V22 introduces no compatibility adapter. The following items must nevertheless
be checked when the authentication migration is completed:

- Keep `countrypolicy_class_inc.php` only while useradmin owns user-country
  selection; move it if canonical identity/profile ownership moves elsewhere.
- Keep `utilities/countries` as the single country catalogue.
- Confirm no duplicate ISO country list was introduced.
- Confirm the new-user default never overwrites an existing user's country.
- Confirm `mod_useradmin_countrynotspecified` remains registered and translated.
- Verify the fresh installer creates all three security-owned authentication
  tables exactly once.
- Verify the fresh installer registers MFA enforcement, grace period,
  persistent-login lifetime, and all visible security language keys.
- Remove this checklist after those ownership and installer checks are recorded
  in `docs/architecture/chisimba26.md`.
