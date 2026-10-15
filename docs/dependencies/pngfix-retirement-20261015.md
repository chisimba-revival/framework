# PNG workaround retirement and legacy generator closure — 15 October 2026

Removed the IE 5.5/6 PNG-transparency plugin from both jQuery modules, its
`loadPngFixPlugin()` API and the two remaining calls in CMSAdmin and Shorturl.
Removed User Registration's unreferenced Supersleight script and helper image.
Modern browsers render transparent PNG images natively.

A read-only dbTable scan of text and relevant varchar columns on all six sites
found no stored references to these plugins or Supersleight. The scan covered
82, 129, 81, 188, 181 and 181 columns respectively on Chisimba, KengaLearn,
LearnTheBirds, KengaPub, KengaSolutions and HeritageTreazures.

The old `phpunit` module is a browser-based code generator, separate from the
PHPUnit testing library. Its controller allowed anonymous dispatch to generation
and file-writing services. Catalogue registration alone does not prevent this
framework from loading a controller. The module is unregistered everywhere;
its source exists on the first three sites. Its controller now returns HTTP 410
for every action without initialising generator, analyser or storage services.
The module source and any existing data are preserved pending Derek's decision
between retirement and pausing. No production generation action was exercised.

Local validation:

- PHP lint and both repository whitespace checks passed.
- Plugin-loader regression passed on host PHP and the local PHP 8.5 container:
  stale/mixed selections, retained plugins, deduplication, asset existence,
  configured core/UI/theme and unchanged sessions.
- Generator regression passed for GET/POST and all former dispatch actions;
  unexpected service loading fails the test.
- Actual local anonymous generation route returned HTTP 410 with an inert notice.
- Disposable installed-engine browser fixture rendered native PNG transparency;
  retained Validate rejected empty input and Form serialised valid input.
  No browser warnings/errors were reported.
- Normal catalogue updates for already-registered jquerycore and
  userregistration passed locally. No dormant module was enabled.

Versions: jquery 0.057, jquerycore 0.013, userregistration 0.046;
CMSAdmin 1.649, Shorturl 1.008, phpunit 1.002.

Five plugins remain in jquerycore: Validate, Form, Corner, LiveQuery and oEmbed.
This is a bounded dependency removal, not completion of the jQuery migration.
