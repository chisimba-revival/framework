# Country dropdowns

Use `getObject('countries', 'utilities')->getCountryOptions()` for country
select options. The returned map retains country codes as keys, places the
current site's valid configured country first, and sorts remaining display
names alphabetically. An absent or invalid site preference leaves the whole
list alphabetical. `getDefaultCountry()` returns the valid site code or `''`.

Pass a translated code-to-name map to either method when rendering localised
names. The service preserves those codes and labels. `getCountries()` remains
the unchanged catalogue API for non-presentation consumers.

Ordering does not select or overwrite a user's country. Consumers retain their
saved selection and their existing blank/default rules. The legacy utilities
`getDropDown()` and language `country()`, `countryAlpha()` and `dec_country()`
renderers use this ordering, as does user administration's country policy.

The service resolves the configured preference on each call. Multitenant
callers must provide the correct request/tenant-scoped `altconfig` service;
this API does not infer a tenant or introduce global preference caching.

Validation:

- `php app/core_modules/useradmin/tests/country_policy_test.php`
- `php tests/translation/locale_names_test.php`
