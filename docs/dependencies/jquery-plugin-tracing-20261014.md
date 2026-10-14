# Shared jQuery dependency tracing — 14 October 2026

Read-only follow-up to the all-site rollout. No application code, libraries,
module registration, content or production settings changed in this investigation.
Source baseline: framework `4ab9eec6f`, modules `9dfc7326a`.

## Loading paths

1. `skin/classes/skinjavascript_class_inc.php::getJQuery()` loads jquerycore unless
   `SUPPRESS_JQUERY` is set. It reads JQUERY_CORE/UI_THEME configuration and the
   skin-scoped session `plugins` selection. Without registered jquerycore it uses
   the old jquery wrapper, even if the jquery module itself is unregistered.
2. `jquerycore/classes/coreloader_class_inc.php` validates selections against
   `resources/plugin_dependencies.xml`, adds declared dependencies and emits core,
   UI, theme and selected plugins. There are eleven optional plugin entries.
3. `jquery/classes/jquery_class_inc.php` and its helper wrappers append their own
   resource paths. Application modules also load files directly. These bypass the
   jquerycore catalogue, so catalogue removal alone cannot retire a plugin family.

Tooltip and Flexigrid wrappers append their names to the skin session. No external
PHP constructor/class-loader callers of those jquerycore wrappers were found.
No external literal setPlugin/setPlugins calls were found beyond skin forwarding.
This is static evidence, not proof against dynamic class names or old sessions.
Any removal must reject stale selections through the catalogue whitelist and test
mixed retained/retired selections; do not clear users' entire sessions.

## Plugin families and callers

Paths below are relative to `app/core_modules` unless prefixed `modules/`.

| Family | Evidence and implications |
| --- | --- |
| Tooltip | jquerycore wrapper has no discovered external caller. Old jquery also contains loose tooltip copies. Native `ui/tooltip` and `htmlelements/tooltip` are distinct APIs and must not be removed by name matching. Candidate for bounded retirement after direct-copy and stale-session tests. |
| bgiframe, delegate, dimensions | Declared dependencies of jquerycore tooltip. Loader suppresses delegate above core 1.4.2 and dimensions above 1.2.6. bgiframe remains eligible. Older bundled widgets can carry their own copies; retire this catalogue chain separately from those bundles. |
| Flexigrid | jquerycore wrapper has no discovered external caller. The older loader also exposes `loadFlexgridPlugin`; `modules/cmsadmin/templates/content/cms_test_tpl.php` contains an example consumer. A wrapper-only retirement does not justify deleting every Flexigrid asset. |
| Validate | Direct older jquery paths in Textblock, Slate, Switchboard, OER and OERuserdata; Formbuilder has another bundled copy and test usage. These bypass jquerycore's validate entry. Do not delete the whole family because the shared entry appears unused. |
| Form | Older loader invoked by Shorturl and Phpunit. Forms controller has a commented invocation. Worksheet's `worksheetinfo_tpl.php` and `step3_tpl.php` matches are entirely inside PHP block comments, so they are not active form-plugin dependencies. |
| Corner | Active source calls in CMS Admin's icon-panel code use the old wrapper. `htmlelements/roundcorners::show()` has only commented JavaScript and returns a CSS class. Preserve the HTML helper; inspect CSS separately before removing any styling. |
| LiveQuery | Confirmed direct load in `modules/personalspace/controller.php:63`; no LiveQuery calls in Personal Space itself. Its template loads the now-native contextblocks script. Other source users include jqboxy/jqgrid, Forms, Shorturl, Phpunit and CMS Admin. Remove the redundant Personal Space load as a separate small change; retain family assets until those callers are addressed. |
| PNG fix | Old loader used by CMS Admin, Shorturl and Phpunit. This is an obsolete-browser workaround, but remove caller invocations with the assets rather than leaving broken URLs. |
| oEmbed | `modules/oembed/classes/jqoembed_class_inc.php` loads `resources/starfishmod/jquery.oembed.js`, not jquerycore's copy. Wall and the shared `parse4quickembed` / `parse4chiki` filters can reach this wrapper. The chiki `remotewall` path checks module registration. Stored filter text and shared filter dispatch need checking before family removal. |

The older wrapper also exposes DDMenu/easing, accordion menu, Superfish, UI tabs,
SimpleTree, jqGrid, Boxy, Facebox, JFrame, FGMenu, Cluetip and Tablesorter.
Concrete source clusters are CMS/CMS Admin (menus, trees, grids, Boxy, Cluetip),
Forms (tabs/tree/Boxy), and Shorturl/Phpunit (grid/Boxy/form/LiveQuery).
Bookmarks and Statusbar still instantiate jquerycore dialogs. These are separate
caller migrations, not a single safe library deletion.

`context/classes/utilities_class_inc.php::searchBlock__()` contains an old
Tablesorter loader, but no caller of that exact method was found. Do not confuse
it with `searchBlock()` or `searchBlock_()`. Similarly, userregistration's old
loginbox contains a direct jQuery 1.3.2 loader; registration of the module alone
does not establish that this legacy helper is used by today's native login flow.

## Local runtime evidence

Read-only catalogue checks found jquerycore, Worksheet, Personal Space,
Userregistration, Filters, Context and Htmlelements registered locally. jquery,
Textblock, Slate, Switchboard, OER, OERuserdata, Wall, oEmbed, Formbuilder, Forms,
CMS, CMS Admin, Shorturl, Phpunit, Bookmarks, Statusbar and Simplegal were not.
Unregistered does not mean unreachable: shared objects, filters and composed
blocks may load classes/resources without visiting the module's controller.

Chrome on local Personal Space, authenticated administrator in the existing test
course, observed these script URLs:

- `core_modules/jquerycore/resources/core/1.7.2/jquery.min.js`
- `core_modules/jquerycore/resources/ui/javascript/1.8.18/jquery-ui.min.js`
- `core_modules/jquery/resources/jquery.livequery.js`
- `core_modules/context/resources/contextblocks.js`

The page rendered and captured console warnings/errors were empty. No block was
added, moved or removed, and no preferences were saved. This is dependency
observation, not a behavioural test with LiveQuery disabled.

A dbTable-backed read-only scan of 195 candidate text/configuration columns
found no matching plugin resource paths, named loader calls, `quickembed:` or
`remotewall` strings. It emitted only table/column/count metadata, never values.
It does not cover every possible encoding, arbitrary alias, filesystem session,
external page or production database. Existing browser session selected none of
the optional jquerycore plugins on the observed page; other sessions were not read.

## Next bounded work

1. Remove the redundant Personal Space LiveQuery load and commented Worksheet
   remnants. Verify composed blocks and native editing with disposable local
   fixtures, failure paths and browser checks. No global library deletion yet.
2. Retire unused jquerycore tooltip/Flexigrid wrappers and their catalogue entries
   only after a focused direct-resource scan and stale-session regression. Separate
   the tooltip dependency chain from unrelated bundled copies and native tooltips.
3. Review the optional-module caller clusters above with per-site registration and
   stored-content evidence before deciding migrate/pause/retire. Keep paused
   modules paused. Review security of write endpoints before changing their UI.
4. Consolidate remaining shared wrappers, then consider conditional core/UI loading.
   Do not switch off global loading or upgrade jQuery as part of this trace.

## Reproduction and limits

Run `python3 tools/dependencies/audit-jquery.py` from the framework checkout.
Current text inventory: 1,238 non-paused candidate files across 66 owners, plus
five paused candidates. These include vendors, comments and Prototype dollar
syntax; they are not counts of active dependencies.

Local evidence is under `work/jquery-tracing-20261014` in the parent workspace:
inventory.json, plugin-files.txt, local-trace.php and local-trace.json. The previous
wide inventory remains a dated baseline; Profile, course block editing and Calendar
have since migrated. No production dependency-free claim follows from this local
audit, and no deletion has been performed.
