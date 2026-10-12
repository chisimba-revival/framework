# HTML sanitisation consolidation — 12 October 2026

## Implemented and tested locally

The framework's HTML Purifier 4.2.0 is replaced by upstream 4.19.1.
SafeHTML 1.3.7 and its bundled HTMLSax3 parser are removed. The public
`utilities/htmlcleaner` API now delegates to `ChisimbaHtmlSanitizer`, the same
maintained purifier used by the engine and Essay. Null input remains null.
Utilities registration is 2.024; use the normal catalogue update path.

The shared legacy policy preserves headings, lists, tables, classes, safe CSS,
code formatting and Unicode, while removing scripts, event handlers, embedded
active content and unsafe URLs. URI schemes are limited to HTTP, HTTPS and
mailto; relative references remain supported. Definition caching is in memory
within the request, without writing vendor-directory cache files. Engine
`purifyArray` now retains sanitised nested values and preserves integer keys.
This does not enable the historically disabled global request sanitisation.

The existing native `richtextsanitizer` used by content blocks and SimpleBlog
keeps its deliberately stricter allowlist. Do not substitute the more permissive
legacy policy into those callers. Stored content is not rewritten by this change.
Existing cached file previews are not invalidated.

## Caller trace

- Site Pages: older `body_html` pages; composed pages use their existing service.
- Filemanager: generated source-code and feed previews before caching.
- Essay: engine purifier for written submissions.
- Assignment, Practicals and CMS administration: utilities htmlcleaner wrappers.
- Paused Blog: same wrapper if it is ever deliberately revived.

PHPIDS has a separate old vendor copy under
`modules/phpids/resources/phpids-0.6.3.1/lib/IDS/vendors/htmlpurifier`.
Its monitor uses purification as part of intrusion detection, with a different
ID/cache policy. This slice does not change that module or claim that its copy
has been removed. Trace its installation and security hooks before retiring it
or replacing its vendor integration. Do not activate it merely to test removal.

## Vendor provenance

Source: https://github.com/ezyang/htmlpurifier/releases/tag/v4.19.1
Archive: https://codeload.github.com/ezyang/htmlpurifier/tar.gz/refs/tags/v4.19.1
SHA-256: `e28be6193246a7d1b864bb72f84756a27e9ab4008d8fca23396e02aaf2dd9e6e`

The upstream `library/` files are copied without code changes into `app/lib/`.
Licence and credits are retained as `HTMLPurifier.LICENSE` and
`HTMLPurifier.CREDITS`. Upstream has three trailing-whitespace lines in
AttrDef/CSS/FontFamily.php and Config.php; retained for a reproducible vendor
copy. Application changes pass diff whitespace checks. Old dev-environment
`fix-htmlpurifier-autoload*.sh` operators target the retired library and must
not be run against this version.

## Validation

- PHP 8.5 lint: all 242 upstream PHP files pass.
- Strict E_ALL behavioural tests: utilities `htmlcleaner_test.php` and
  `tests/core/html_purifier_engine_test.php` pass, including malicious markup,
  rich content, Unicode, repeated calls, nested arrays and policy separation.
- Existing richtext sanitizer, four Site Pages tests and ten Essay tests pass.
- SafeHTML retirement guard passes without loading PEAR or HTMLSax.
- Separate old/new purifier processes produce identical rich-content fixture
  output; old 4.2 emits PHP warnings, 4.19.1 emits none.
- Local assembled PHP 8.5 vendor library synchronised; local home and published
  Site Pages policy render in the browser. Utilities catalogue update applied.
- Temporary browser fixture verifies 4.19.1, visible Unicode/table/blue code
  formatting, working anchor markup and absence of script/iframe/onclick nodes.
  Fixture removed after testing. No PHP warning/deprecation/fatal/notice found
  in container logs for the validation window.

No production deployment is included in this slice. Deploy the complete new
vendor tree with the engine/helper/wrapper; remove SafeHTML and old vendor files
as part of the same release. Validate representative legacy authored content on
a disposable site clone before production rollout. Do not reclean stored rows
or remove user content as part of deployment.

## Agreed follow-up work

1. Resolve the separate PHPIDS vendor dependency and obsolete repair operators.
2. Remove retired OpenID and Twitter login libraries, tracing callers first.
3. Replace verified Flash/Java media callers with native media and remove assets.
4. Migrate ExtJS callers completely and remove ExtJS.
5. Plan staged jQuery consolidation with caller inventory and browser coverage,
   using the same care as the PEAR migration.
