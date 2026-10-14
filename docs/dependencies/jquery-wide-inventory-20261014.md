# Wider jQuery inventory and ImageFit retirement — 14 October 2026

This is an incremental source/local cleanup, not a jQuery upgrade or a production
release. jQuery and jQuery UI remain in use.

## Reproducible inventory

Run `python3 tools/dependencies/audit-jquery.py` from the framework checkout;
use `--modules /path/to/modules` if the modules repository is not its sibling.
The output lists paths and first matching line numbers, not source text or values.
It covers framework application code and modules, including resource bundles,
while separating `__PAUSED` paths and excluding tests/docs. It detects named
jQuery references, jQuery calls, possible dollar calls, wrapper loaders, legacy
plugin methods and possible UI widget calls. Headers provide version candidates.

After this slice: **1,243 non-paused candidate files across 66 owning directories**;
202 candidates outside `resources` directories. Five paused files also match.
There are 26 files matching shared loader calls and 20 matching legacy plugin
loader methods. These are overlapping text signals, not live dependency counts.
Comments, bundled libraries, examples, unregistered modules and Prototype's `$()`
contribute matches. Arbitrary aliases and dynamically constructed names may be
missed. Files outside resource directories are not necessarily first-party code.
Never delete a dependency solely because this inventory finds no textual caller.

Local machine-readable results and observed browser script URLs are under
`work/jquery-wide-20261014/`; rerun the committed tool to regenerate source evidence.
No production inventory is claimed here.

## Observed live baseline

Chrome, authenticated local administrator, current course `sectionjourney101`:
course home, My Profile and File Manager each load exactly these named jQuery
core/UI resources:

- `jquerycore/resources/core/1.7.2/jquery.min.js`
- `jquerycore/resources/ui/javascript/1.8.18/jquery-ui.min.js`

All three pages loaded with no captured console warning/error. PHP container
logs showed no warnings/deprecations during these smoke checks. No form was saved.

Local registration: jquerycore, userdetails and calendar are registered. The old
jquery module, formbuilder, bookmarks, cms, cmsadmin, forms, shorturl, simplegal,
statusbar and schoolusers are not. Unregistered status is not deletion authority
or proof that reusable classes cannot be loaded.

## Completed bounded cleanup: ImageFit

Removed the unused jquerycore `imagefit` helper, catalogue entry, all three
bundled plugin copies (jquerycore plugin, jquery plugin, jquery loose resource),
and the old jquery wrapper's `loadImageFitPlugin()` method. Removed the two
commented File Manager invocations. Full PHP/JS/XML source search found no
remaining ImageFit callers; historical registration notes remain as history.
A local scan of 195 candidate stored-content columns found no ImageFit references.

The removal manifest is `tests/dependencies/retired-imagefit-paths.json`.
Versions: jquery 0.056, jquerycore 0.011. Only registered jquerycore was updated
locally, with normal pre/post catalogue hooks; the dormant jquery module was not
installed. Existing native responsive image presentation is unchanged.

Checks: PHP lint, whitespace checks, stale-session filtering/retirement regression,
native document preview and native image-picker contracts pass. The regression
passes an old session containing both Flash and ImageFit: neither is loaded,
while a supported tooltip selection remains intact. Browser smoke checks above
ran after removal. No database schema or user content changed.

## Migration order supported by current evidence

1. **My Profile / User Details:** a live, bounded workflow. Migrate the dialog
   notices and photo update/reset handlers together with the optional school
   selector. Source still uses jquerycore dialogs, jQuery Ajax and autocomplete.
   Preserve drafts and verify POST/CSRF, image ownership/type checks, failures,
   duplicate submission protection, keyboard use and the optional school branch.
   Review the server endpoints before replacing client code. Do not test profile
   writes or password changes on the administrator's real record.
2. **Course block editing:** the live `contextblocks.js` uses jQuery for incremental
   edit/move/remove/preview interactions. Preserve server permissions and CSRF;
   test with disposable course/block fixtures, including failures and ordering.
3. **Registered module callers such as Calendar**, then optional module workflows
   after registration and route review. Keep dormant modules dormant.
4. **Shared wrapper consolidation:** tooltip and its dependencies, flexigrid,
   older tabs/grids/menus, form/validation plugins and duplicate copies. Eleven
   optional jquerycore plugins remain after ImageFit. Account for both loaders,
   stored selections and direct module resource loads.
5. Remove unconditional skin loading only after consumer migrations and page-level
   verification. Any interim library upgrade needs a separate compatibility plan
   and current upstream checks. This slice changes no library version.


## Completed caller migrations later on 14 October

- My Profile: see [native profile report](native-profile-20261014.md).
- Course block editing: see [native course block report](native-course-blocks-20261014.md).

The inventory above is a dated starting point, not a claim that these callers
still depend on jQuery. Calendar is next; global skin loading remains.
