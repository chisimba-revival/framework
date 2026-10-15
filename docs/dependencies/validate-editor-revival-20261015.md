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

## Security checkpoint — later on 15 October

Textblock 0.893 and Slate 0.016 now check the current administrator role, POST and
login-bound CSRF before mutations. Slate dispatch has an explicit action allowlist;
its management/read endpoints also require an administrator. The actual controller
probe verifies rejected delete requests never reach persistence. Public Slate
viewing remains available.

Server validation rejects malformed IDs/arrays, invalid block slots, oversized or
empty titles, unsafe CSS identifiers and invalid page numbers. Saved Slate page
numbers cannot be changed through a forged edit. Textblock uses the maintained
rich-text sanitiser; titles, descriptions and form values are escaped. Serialized
save operations reject duplicate block slots/page numbers without overwriting
existing records. Slate deletion is transactional and handles missing records.

Slate block selection is limited to registered site blocks, site-scoped dynamic
blocks and current Contentblocks rows. The chooser follows the same site scope;
private user dynamic blocks are not offered on a public page. Removal/movement
checks the placement against the requested page. Canvas 0.048 binds page-block
reads and swaps adjacent positions transactionally; a failed second write rolls
back the first. Slate's client sends POST and the matching CSRF token. Other
Canvas owners retain their existing HTTP-method contracts and need separate review.

Both principal editors now use native validation instead of loading Validate.
Failed Ajax requests retain the draft and re-enable Save; only confirmed success
updates Slate's ID/mode. Administrator Help explains immediate saves, duplicate
identifiers, session recovery and deletion. Textarea 0.622 and explicit editor
properties remove the PHP 8.5 warnings exposed by rendering these forms.

Checks completed:

- Standalone policy tests, including anonymous/member/revoked-admin, method,
  token, malformed fields, unknown actions, unoffered blocks and cross-page IDs.
- Actual controller dispatch probes: rejected deletes make zero repository writes;
  valid administrator POST delegates once.
- Full disposable PDO suite, including real editor saves, sanitisation, duplicate
  rejection, stable page numbers, scoped deletion and block-move rollback.
- Registered Help-provider content and administrator access tests.
- PHP syntax, JavaScript syntax and diff checks.
- Local Chrome tests on synthetic responses rendered with the actual form classes:
  empty required fields, rejected-save draft retention in both editors, Slate
  success and network-failure recovery. Normal local homepage smoke also passed.
  Browser diagnostics were from Grammarly, not the application scripts.
- Form regeneration after the property fixes emitted no PHP diagnostics.

This is a tested security checkpoint, not a production release or completed
module revival. No module has been installed/enabled and no site data was changed
by the fixtures. Full catalogue fresh/update rehearsal, normal authenticated
browser journeys (including Help interaction and responsive layout), stable Slate
page URL/placement identity, concurrent editor revision protection and the stale
Contentblocks classes inside Textblock still need review before activation.
Switchboard and the remaining Validate copies/callers are unchanged.
