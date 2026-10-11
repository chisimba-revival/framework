# Native metadata and BBCode contracts

Run `php tests/utilities/native_xml_test.php --compare` and
`php tests/utilities/native_bbcode_test.php` with the supported PHP extensions.
The XML comparison deliberately loads the retained vendor oracle after proving
that the native path did not load PEAR. The BBCode JSONL fixtures were captured
from the retained PEAR parser with its PHP 4 constructor explicitly invoked and
the six filters in Utilities' historical `BBCodeParser.ini`. Neither vendor
implementation is used by the new application wrappers.

## BBCode

The parser supports b/i/u/s/sub/sup, color/size/font/align, quote/code,
url/email/img, list/ulist/li and `[*]`. Nested formatting, scalar attributes,
image dimensions, list type/start/value, explicit links and automatic links are
covered. Unknown tags remain text. It no longer depends on the process working
directory or global PEAR option state. Inputs over 1 MiB, over 10,000 markup tokens
or deeper than 63 open BBCode tags are returned unchanged.

Intentional differences from the legacy engine:

- Code blocks display their contents literally, including HTML/BBCode.
- Malformed crossed tags close open formatting without creating empty reopened
  elements. Unmatched recognised closing tags are discarded.
- Generated attributes are escaped. URLs reject control characters and unsafe
  schemes; images use HTTP(S) or relative URLs. CSS values are restricted rather
  than accepting arbitrary style fragments. Font sizes are 1–144 pt; image
  dimensions are positive integers up to 4096.
- New-window links add noopener/noreferrer. Bare `www.` URLs gain HTTPS. Modern
  email suffixes are accepted rather than the legacy two-to-four-letter limit.
- Existing HTML tags/attributes, comments and complete a/script/style/code/pre
  elements are preserved verbatim. BBCode inside those opaque elements is not
  expanded. This avoids rewriting attributes or creating nested auto-links.

**This formatter is not an HTML sanitiser.** Rich HTML is already produced by
washout filters and other callers; sanitisation remains the content owner's
responsibility. Do not use it as a security boundary for arbitrary submitted HTML.
Before deploying to a site using old blog profiles or custom filters, compare
representative stored content, especially the intentional differences above.

The unit suite tests the real public wrapper from a different working directory
and washout's enabled/disabled delegation, with unrelated filters stubbed. It does
not claim every old washout plugin is PHP 8.5 compatible. For the real installed
engine on a disposable fresh site:

```
CHISIMBA_INSTALLER_FIXTURE=1 php tests/utilities/installed_bbcode_smoke.php /installed/ch
```

The local installed-module catalogue was also opened successfully in the browser,
exercising its description-formatting path. Vendor files and the old INI remain
for the final dependency/build cleanup stage; the native wrapper ignores the INI.

## Content-expiry notices

The PDO and MDB2 contract runners include `content_expiry_checks.php`. For an
explicitly disposable, transaction-capable installed site, the public queue API
can also be checked without delivery:

```
CHISIMBA_INSTALLER_FIXTURE=1 php tests/utilities/installed_expiry_smoke.php /installed/ch
```

This queues a synthetic `.invalid` address inside a transaction and verifies that
the row disappears on rollback. It does not run a worker or enable a scheduler.
