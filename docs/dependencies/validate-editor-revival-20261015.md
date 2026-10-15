# Validate callers and editor revival — 15 October 2026

Derek has requested preservation and modernisation of Textblock and Slate.
Switchboard is a possible retirement, not an approved deletion. Keep all three
available in source while resolving their future. Do not enable old editing
endpoints on a site merely to exercise the Validate plugin.

## Production evidence

A read-only dbTable scan covered text columns and varchar columns whose names
include script, style, URL, link, HTML, content, value, template, parameter,
module, block or page. It returned counts and column names, never content values.

| Site | Columns checked | Legacy editor tables |
| --- | ---: | --- |
| Chisimba | 125 | None |
| KengaLearn | 174 | None |
| Learn the Birds | 124 | None |
| KengaPub | 244 | Two empty Slate tables |
| KengaSolutions | 237 | Two empty Slate tables |
| Heritage | 237 | Two empty Slate tables |

Textblock, Slate and Switchboard are unregistered everywhere. The three sites
with empty Slate tables also retain Slate catalogue/menu/block definitions.
Do not discard those definitions as part of this lookup fix. Other broad-word
matches are language metadata, two webinar payloads containing “slate”, and one
KengaLearn logger parameter containing “switchboard”; these are not evidence of
active module use. No stored Validate asset reference was found. No Switchboard
dependency was found outside its module in the PHP/JS/registration source scan;
external matches were copied package comments.

The scan is evidence, not proof against dynamically assembled references,
encoded values, external clients or unexamined column types. Its counts-only
receipts and scanner are in `work/validate-trace-20261015` in the parent workspace.

## First safety change

Textblock 0.892 and Slate 0.015 use dbTable prepared reads for editor record IDs.
Textblock also binds its block-name lookup, uses the safe catalogue row lookup,
rejects a block belonging to another module, and handles absent catalogue/content
rows without an undefined-index warning. Its used-block lookup accepts the two
supported families, `text` and `widetext`, and binds their prefixes.

The disposable PDO suite exercises real repositories against synthetic tables:
valid and quoted IDs, injected IDs, missing rows, foreign module blocks, both
block widths and invalid/wildcard block families. The complete PDO suite passed
on PHP 8.5.11 without emitted warnings. Syntax and diff checks also passed.
These are database checks, not browser acceptance or a completed module revival.

## Remaining work before enabling either module

1. Textblock: enforce POST and CSRF on save/delete; keep administrator-only
   editing; validate fields on the server; escape rendering and use the shared
   rich-text boundary; retain drafts on errors. Trace the obsolete Contentblocks
   classes bundled inside Textblock before removing them.
2. Slate: enforce administrator permissions, explicit action dispatch, POST and
   CSRF; validate page identifiers and uniqueness server-side; handle save/delete
   failures safely. Audit page deletion and block persistence as one workflow.
3. Slate's Canvas page-block editor must be brought onto the maintained shared
   block-editor mechanisms. The old pageblockajax handler itself has no method,
   CSRF or permission boundary. Do not add guards to its callers without also
   updating the client and validating page/block scope and stale edits.
4. Replace Validate in the restored editors with native validation, add contextual
   Help, and test authorised/unauthorised flows, draft recovery, narrow screens
   and keyboard operation in a disposable local installation.
5. Rehearse catalogue updates, preserve the existing empty Slate metadata, then
   deploy the completed change through the normal six-site release workflow.

Validate remains required by other source callers in OER and OERuserdata, the
unresolved Switchboard editor and deprecated Pagenotes. Formbuilder has a bundled
copy with no loader found in this trace. The jquerycore Validate catalogue entry
and the older jquery copy must remain until those dependencies are resolved.

This checkpoint does not retire a plugin, enable a module or change production.
