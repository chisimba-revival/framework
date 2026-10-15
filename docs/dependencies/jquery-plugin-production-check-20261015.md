# jQuery plugin production reference check — 15 October 2026

Follow-up to [the loading-path trace](jquery-plugin-tracing-20261014.md), after
rolling out the Personal Space repair. This is evidence for the next bounded
retirement, not a claim that jQuery is unused.

A source scan again found no literal class-loader calls to jquerycore’s tooltip
or Flexigrid wrappers, and no direct literal resource paths into its tooltip,
Flexigrid, bgiframe, delegate or dimensions plugin directories. The two tooltip
constructor matches belong to the separate UI/htmlelements APIs. Skin’s session
forwarding remains the relevant `setPlugins` caller; the unrelated Zend editor
has its own method with the same name.

A dbTable-backed, read-only scan checked text columns and relevant varchar
columns on all six production sites. Patterns targeted jquerycore resource paths
and paired jquerycore/plugin names; only table/column/count metadata was returned.
No stored content values, credentials or sessions were exported.

| Site | Candidate columns checked | Matching columns |
| --- | ---: | ---: |
| chisimba | 82 | 0 |
| heritagetreazures | 181 | 0 |
| kengalearn | 129 | 0 |
| kengapub | 188 | 0 |
| kengasolutions | 181 | 0 |
| learnthebirds | 81 | 0 |

The scan does not cover encoded/generated names, all possible varchar columns,
external pages or filesystem sessions. It intentionally does not identify older
jquery-module or module-bundled copies as jquerycore dependencies. All six sites
register jquerycore; CMS/CMS Admin, Forms, Shorturl, Phpunit, Bookmarks, Statusbar,
Textblock, Slate, Switchboard, OER/OERuserdata, Wall, oEmbed and Formbuilder remain
unregistered. Unregistered modules are not automatically safe to delete.

Next: exercise the loader with old tooltip/Flexigrid/dependency selections mixed
with retained plugins, then remove only the unused jquerycore wrappers, catalogue
entries and their assets. Preserve unrelated native tooltip helpers and older
plugin copies until their callers are migrated. Global core/UI loading is unchanged.
No plugin was deleted or disabled by this check.

Reproduction evidence is in `work/jquery-retirement-check-20261015` in the parent
workspace: counts-only site reports, source matches and the guarded-by-CLI dbTable
scan. Source pins were framework `a0c7d3c27`, modules `f0ba1f356`.
