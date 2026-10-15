# Validate callers and editor revival — 15 October 2026

Derek has requested preservation and modernisation of Textblock and Slate.
Switchboard was subsequently approved for retirement and removed from source;
see the final checkpoint below. Preserve Textblock and Slate. Do not enable old editing
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

## Installed rehearsal and concurrency checkpoint

Textblock 0.894 and Slate 0.017 now capture row revisions with the displayed
content and compare them inside the save/delete transaction. Stale saves return
409 and retain the draft. Slate returns its new revision from inside the same
transaction, so a subsequent save cannot silently adopt another editor's revision.
Slate deletion additionally checks the placement snapshot: a new block added since
the management list was opened prevents stale deletion. The repositories use
savepoints and respect enclosing transaction ownership.

The request boundaries now use persisted `lookupAdmin(userId)` membership. The
legacy `isAdmin()` helper can return a cached session flag; it is insufficient
for these editing decisions. Both standalone fixtures and actual authenticated
HTTP tests cover revocation while an administrator session remains open.

Slate uses `md5('slate:page:' + persisted ID)` and a separate `slate:default` key.
Its page renderer, native client, placement repository and page deletion agree on
that identity. Unknown page numbers return 404; forged/deleted page keys cannot
receive placements. It now uses the maintained Context/Personal Space native
block client and shared `blockplacementorder` service, including stale-layout
rejection, uncertain-write lockout and keyboard movement. Rendering uses one
placement snapshot and refuses to execute blocks no longer in the site catalogue.
The shared Canvas renderer accepts an explicit page key/repository/editor settings;
existing callers retain their defaults.

A repeatable catalogue post-install hook migrates the known canonical legacy URL
hashes (normal route and explicit `action=view`). It builds these from the
configured site root, not the request URI: the populated catalogue rehearsal
caught and fixed the CLI operator-script URL being used during upgrades. Arbitrary old query strings,
previous hostnames, rewritten URLs and tracking parameters cannot be recovered
from an MD5 hash. **Unknown hashes remain stored and are not rendered under the
new keys.** Before enabling a site with historical placements, inspect them and
map only URLs supported by evidence using `dbslatepages::migratePageKeys()`; do not
invent mappings or delete unresolved rows. The previous six-site trace found no
existing placement data, but recheck at release time.

The disposable installed rehearsal used an isolated clone of local Chisimba,
a temporary internal Docker network/database, a separate installation key,
loopback port 8087 and no outbound connectivity or workers. The real local site
and production data were not modified. Catalogue fresh/repeated registration
passed; it exposed Slate's incompatible 2012 free-text release date, now ISO.
The table-based Slate form overflowed at phone width; it now uses shared form
primitives, registered labels and a wrapping description textarea. Textblock's
checkbox now has an associated label and its fields use shared width rules.

Completed verification:

- Full disposable PDO suite on PHP 8.5, including stale saves/deletes, shared
  placement ordering, failed-write rollback, known-hash migration/repeat,
  unresolved-hash preservation and deleted-page rejection.
- Actual HTTP GET/token/anonymous/member rejection for both modules, persisted
  role revocation against an open admin session, unchanged data fingerprints,
  public Slate viewing and missing-page 404. Valid scoped block removal and
  both confirmed deletion endpoints also passed using only synthetic records.
- Chrome: installed Textblock create/edit; Slate create/repeated save; separate
  tab stale drafts retained in both editors; block preview/add/keyboard movement;
  stale layout rejected; reordered URL parameters retain the same placements;
  removal confirmation cancelled with Escape and focus returned.
- Both compact Help entries and full guides; Escape closes guides and returns
  focus. Real forms rendered inside 390px same-origin frames after the browser
  viewport override proved ineffective. Form controls fit; the existing shared
  navigation still extends about 8px beyond the scrollbar-adjusted viewport.
- Shared native block-client tests for Context, Personal Space and Slate;
  existing Context/Personal Space policy tests; editor script/policy/controller
  and Help tests; changed PHP/JS syntax and Git whitespace checks.
- No new application PHP warnings/deprecations in the completed installed
  workflow. Initial fixture setup/invalid release-date failures were diagnosed
  and corrected. Browser console messages were from Grammarly, not these scripts.

This checkpoint does not enable Textblock/Slate on the working local site and
has not been deployed. Before release, review the obsolete Contentblocks classes
bundled in Textblock and complete the remaining Validate dependency decisions.
Switchboard remains preserved pending a decision. Other owners of the legacy
Canvas Ajax handler still require their own access review; this change does not
claim to modernise every Canvas or jQuery caller.

## Unused Textblock copies removed

Textblock 0.895 removes `contentblockbase`, `contentblockui` and `dbcontentblocks`
from its own classes directory (882 lines). A tracked-source trace across both
repositories found no external caller of these Textblock copies. Its controller,
templates and registered text/widetext blocks use `dbtextblock`, `textblockui`
and `textblockbase`. Framework Blocks, Canvas, Context, Prelogin and Postlogin,
and application Personal Space and Slate explicitly resolve the maintained
`contentblocks` module. That module and all stored content remain unchanged.
No aliases are added for these unreferenced copies. Textblock now has no Validate
loader; this does not remove the shared Validate library.

Focused Textblock policy/controller/Help/native-client tests and maintained
Contentblocks edit/render and legacy-postlogin contracts passed. Git whitespace
checks passed. This source-only deletion changes no browser workflow; the prior
installed browser/PDO rehearsal remains the workflow evidence, not a new test run.

The remaining exact asset loaders in tracked application source are:

- OER: adaptation, products, sections, themes, groups, institutions/types,
  downloader and user editors (ten loader locations across nine class files).
- OERuserdata: user editor (one loader).
- Switchboard: link editor (one loader); retirement/pause decision requested.
- `pagenotes_DEPRECATED`: old editor (one loader), separate from maintained Pagenotes.
- Formbuilder retains its bundled Validate file; no loader was found. A comment
  in its template credits a copied email expression and is not a plugin load.

Security follow-up takes precedence over replacing these client assets.
OERuserdata's `requiresLogin()` permits `userdetailssave` anonymously; that action
accepts `mode=edit` and its repository passes the submitted ID and account fields
into the user-admin update service, with interpolated SQL for the extra-data
lookup. End-to-end service enforcement and current site registration still need
tracing before any exposure claim. Do not enable it for testing on a working site.
Switchboard's link-save action lacks a local administrator/method/token boundary,
and its delete checks cached administrator state without POST/CSRF. Its old
module-block operations also delegate to the legacy Canvas handler. The earlier
six-site trace found Switchboard unregistered; recheck before release.

Next: resolve Switchboard's disposition, audit OER/OERuserdata use and full account
write boundaries, then migrate or explicitly retire remaining callers. No library
removal, production deployment, module activation or data deletion occurred here.

## Switchboard retirement approved and completed

Derek explicitly chose retirement on 15 October. All 17 Switchboard source files
were removed, including its Validate loader and legacy Canvas Ajax callers.
A new tracked-source scan found no external runtime references or dependencies in
framework/modules; only copied package comments and historical reports remain.
See `modules/docs/legacy-module-revival.md` for scope. No database changes or
production deployment occurred. Earlier pending-decision notes are superseded.
OER/OERuserdata account-boundary tracing is now the next security priority.

## OER pause and Validate asset removal

OER and OERuserdata are preserved under __PAUSED names; all 195 original files
are retained byte-for-byte. No external active dependency was found. Deprecated
Pagenotes is already unregistrable and remains migration reference only. Its old
loader and those in paused source require migration before any revival.
Formbuilder's bundled Validate copy has no loader/API caller; its separate
constructed-form validator remains unchanged.

Removed the jquerycore Validate 1.9 asset/catalogue entry, both older jquery copies
and Formbuilder's unused copy. The now-empty catalogue requires validPlugins to
start as an empty array so stale session selections cannot cause PHP TypeErrors.
The actual loader/skin regression covers empty catalogue, stale/mixed/duplicate
selections, both setter APIs, unchanged sessions, core/UI/theme and suppression.
It passes with PHP warnings treated as failures. Maintained Pagenotes contracts
and Textblock controller-security tests also pass.

Local browser acceptance remains pending: Docker had no running containers and
browser automation failed twice loading its request-header policy. No deployment
or database mutation occurred. Complete local smoke tests before release.

The refreshed read-only six-site scan completed successfully: OER, OERuserdata
and Formbuilder are unregistered on all six sites, with no stored Validate asset
references in the scanned text/relevant varchar columns (125/174/124/244/237/237
columns respectively). This is bounded scan evidence, not proof about encoded or
external references. Receipts remain in work/validate-trace-20261015.
