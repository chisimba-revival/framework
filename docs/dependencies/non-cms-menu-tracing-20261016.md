# Non-CMS tabs/menu cleanup — 16 October 2026

Removed unused jquerycore tabs/accordion PHP wrappers and two unused methods in
the old jquery wrapper: loadDDMenuPlugin and loadJFramePlugin. Their dropdown and
AJAX-frame asset files were already absent. Cross-repository searches, including
multiline object-loading calls and direct resource/plugin names, found no active
consumers. This does not remove jQuery UI tabs/accordion from the bundled library.

Kept CMS jqtabs/jquerytabs, Boxy, grids and menu helpers. SIS has a genuine caller
of the old jqtabs abstraction (requested via htmlelements) for student details and
schedules, and depends on FileMaker Pro. Do not delete or activate SIS without a
separate usage/security review. CMS remains outside this cleanup round.

Refreshed audit output: work/menu-helper-trace-20261016/inventory.json in the parent
workspace. Before this slice it found 1,077 non-paused candidate files, 9 legacy
plugin-loader candidate files and 13 shared-loader candidate files. These overlap
and include vendors/comments/Prototype syntax, not counts of live dependencies.

Stored-reference scans for the two retired loaders/resource paths are recorded in
the same directory. Source tracing, not those narrow terms, establishes the unused
PHP wrapper candidates. Arbitrary dynamic aliases/external callers are outside
this evidence. No production source or data changes were made.

Passed: retirement boundary test (including retained CMS methods), actual shared
loader/stale-session test, PHP lint and whitespace checks. Chrome local Personal
Space rendered and its editing toggle opened/closed controls with no console
warnings/errors; no content was changed. Versions: jquery 0.066, jquerycore 0.020.
Global jQuery 1.7.2 and UI 1.8.18 loading is unchanged. Next: SIS review and remaining
direct resource/helper callers before conditional global loading.

Completed local and six-site loader/resource reference scans: zero matching
columns on every installation.
