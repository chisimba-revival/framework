# Flash utility retirement — 14 October 2026

This slice is source/local only. No production release or user-data changes.

## Removed

- File Manager's orphan `ZeroClipboard.js` and `.swf`. Source tracing found no
  callers; the maintained native file-information template already uses the
  Clipboard API, with selection/keyboard fallback.
- OpenFlashChart 1.9.6 and the `flashgraph`/`flashgraphdata` wrappers.
- jQuery Core's optional Flash 1.3.1 plugin and its XML loader entry. Stale session
  plugin requests are filtered by the existing loader allowlist.

See `tests/dependencies/retired-flash-utility-paths.json` for deployment removals.

## Caller changes

The Context statistics block explicitly described itself as an incorrect demo.
It requested site-wide module totals from Logger rather than course activity.
Existing block placements now offer a link to the maintained Context Content
activity report. The block renders only for a logged-in administrator or current
instructor in a non-root course, and only when Context Content is installed. The
report independently enforces its own current-course permission boundary and has
contextual Help. It is a content activity report, not a replacement measure of
all module usage.

Logger's old `modulestats` Flash data action and Form Builder's `newtest`
OpenFlashChart developer demo return 410 without creating their normal service
objects or reading data. Other Logger and Form Builder actions are retained;
this does not certify their wider legacy functionality. Form Builder's separate
jqPlot `graphtest` remains for its later dependency review.

The warning-as-error regression test exposed 24 deprecated semicolon switch
labels in Form Builder under PHP 8.5. These now use colons, preserving behaviour.
Controller service properties are declared to avoid dynamic-property warnings.

Versions: Context 2.055, Logger 1.010, Utilities 2.025, jQuery Core 0.010,
Form Builder 0.20. The four registered core modules were updated locally through
catalogue registration with pre/post hooks. Form Builder was not installed or
revived. Deploy framework and modules together to remove its old wrapper caller.

## Validation

- PHP lint and diff whitespace checks pass.
- `flash_utility_retirement_test.php` checks anonymous/student/outsider denial,
  lecturer/admin visibility, revoked instructor denial, root/missing-module
  behaviour, escaping, both retired controller actions (including init), stale
  session plugin filtering, supported tooltip retention and removed paths.
- Context Content activity-report behavioural tests pass: scoped queries,
  permissions, dates, paging and CSV formula protection.
- Native document-preview and image-picker regression tests pass.
- Module Catalogue dependency/update tests pass.
- ExtJS retirement guard passes across 9,210 active source files.
- Local stored-content scan: 195 candidate columns, zero ZeroClipboard,
  OpenFlashChart, jquery.flash or modulestats references. Production content was
  not scanned in this slice.
- Chrome: maintained content-activity report and contextual Help load; File
  Manager and folder navigation load. The browser rejects navigation to the
  intentionally retired Logger route; controller tests establish the 410 result.
  An attempted existing-image detail check redirected to the current course's
  File Manager, so native Copy was source-traced but not click-tested here.
  The replacement statistics block was covered by behavioural tests, not a new
  persistent browser block placement. No PHP warnings/deprecations in local logs.

## Optional jQuery plugin map — retained for staged migration

The remaining `jquerycore` XML catalogue contains 12 plugins. Filename-only
search is insufficient: `skinjavascript` consumes session plugin selections;
imagefit, flexigrid and tooltip helpers populate that session dynamically.

| Group | Evidence and next step |
| --- | --- |
| tooltip | Shared wrapper; declares bgiframe, delegate and dimensions dependencies. Migrate the complete wrapper and its consumers together. |
| imagefit, flexigrid | Shared wrappers populate the plugin session. Audit dynamic class/content use before removal; use native responsive images/tables for confirmed consumers. |
| validate, form | Filename searches also find module callers and the separate older jquery wrapper. Migrate complete forms, including error and submit handling, before removing copies. |
| bgiframe, dimensions, corner | Older jquery wrapper/drop-shadow references; first map both wrapper stacks rather than treating this catalogue as the only loader. |
| livequery, pngfix, oembed | No direct references to these catalogue filenames found, but corresponding functionality exists in other wrappers/modules (Forms/ShortURL/Wall). Trace copies and behaviour before consolidating. |

The local filename inventory is `work/flash-utilities-20261014/plugin-path-references.json`.
It is evidence for planning, not proof that an unreferenced filename is unused.
No jQuery core/UI version was changed. Paused modules and their data remain intact.
