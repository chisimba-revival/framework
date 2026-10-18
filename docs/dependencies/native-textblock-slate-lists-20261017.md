# Native Textblock and Slate lists — 17 October 2026

Textblock 0.896 and Slate 0.019 no longer load their legacy jQuery list scripts.
Textblock previews use native details/summary, including keyboard disclosure.
Both lists use UI 0.2.4's shared `rowdelete` renderer/client and local icon service.
The client confirms deletion, sends POST with CSRF and revision evidence, prevents
concurrent duplicate clicks and removes a row only on the exact success response.
Errors, redirects, HTML and uncertain network results preserve the displayed row
and explain that its persisted state must be checked before retrying. The live
status survives removal and focus returns to the list. Attributes are escaped;
requests are restricted to the same origin. No automatic write retries.

Existing controllers retain current persisted administrator checks, POST/CSRF,
validation and repository revision/transaction protection. List controls also
check current administrator membership. Responses declare plain text/no-store.
Help covers permanent deletion and uncertain-write recovery. Shared table/icon
primitives replace legacy action icons; wide tables scroll within their wrapper.
No schema migration or change to public Slate reading/block-content ownership.

## Validation

- PHP syntax and whitespace; renderer escaping/single-load test.
- Actual native client tests: confirm/cancel, pending duplicate, POST data,
  success/focus, rejected response, network failure, HTML and cross-origin denial.
- Existing editor, controller, settings, migration and registered Help tests.
- Full disposable PDO contract suite, including real Textblock/Slate saves,
  sanitisation, duplicates, stale layout/deletion, scoped deletion and rollback.
- Fresh catalogue installs then repeat updates in an isolated PHP 8.5/PDO clone.
  No outbound network/workers/uploads copied. Synthetic records/credentials only.
- Real HTTP requests for each module: GET, invalid CSRF, stale revision, anonymous,
  member and revoked cached administrator denied; fingerprints unchanged after
  denied requests; valid deletion succeeds and repeated deletion is rejected.
- Chrome with automatic jQuery suppressed: Textblock keyboard disclosure, cancel,
  successful deletion, concurrent-edit rejection with visible recovery; Slate
  successful deletion; Help quick/full guide and Escape/focus return for both.
  Script URL inspection found no jQuery on either list. Native client tests cover
  network failure; this was not simulated in the live browser.
- Desktop and 390px Slate inspection. Table has local horizontal scrolling; the
  pre-existing shared site shell still has horizontal overflow at narrow widths.
  Do not describe the whole legacy layout as fully modernised.
- Isolated PHP logs had only expected fresh-install no-defaultdata notices and no
  PHP warning/deprecation/fatal. Slate browser console had no captured errors.
  One Chrome tab stalled around confirmation; a fresh tab completed the check.

Real local site: updated UI only; Textblock and Slate remain dormant. Global
jQuery stays enabled on the real site. No production deployment. Disposable
containers, network, generated config and test sessions removed after checks.

## Remaining global-loading blockers

Wall, SimpleGal, ePortfolio upload, old filters/Blogcomments and smaller asset
callers still require tracing/migration. CMS/CMS Admin are deferred for separate
revival and need a deliberate compatibility decision. This batch removes the
Textblock/Slate dependency; it does not certify global jQuery removal.
