# Frontend dependency audit — 12 October 2026

## Scope and evidence

This is an initial source and local-registration audit following interactive-viewer retirement. It makes no runtime changes and does not authorise production deployment. Search matches are candidates, not proof of reachable execution. Vendor examples, comments, dynamically composed names and stored content require separate interpretation. Local registration is not proof of production usage.

## ExtJS findings

Two loaders remain: core `ext/classes/extjs_class_inc.php` uses Ext 3.4.0; `htmlelements/classes/extjs_class_inc.php` targets Ext 3.0-rc2.

| Owner | Evidence and next decision |
| --- | --- |
| context | Course-home loader is inside a comment; no live dependency established there. |
| contextgroups | Old lecturer/student/users/members JS remains. Modern control-panel test explicitly rejects members.js; trace all remaining resource references before pruning. |
| mcqtests | Old choosequestiontype2 template and scripts use Ext, but the controller action now redirects to banks. Candidate dead feature assets, subject to complete caller check. |
| assignment | Old addedit_assignment.js contains Ext. Trace modern template references before deciding removal. |
| contextcontent | Controller still returns usersactivitylog_tpl.php and contextcontentusage_tpl.php; these load Ext and grid scripts. Migrate these reporting screens before removing the library. |
| activitystreamer | Blocks and activities template directly load Ext and grid scripts. Preserve the module for possible revival requested by Derek; modernise before enabling. |
| contextinstructor, practicals, triplestore | Legacy Ext controls remain; local modules are unregistered. Trace route/resource consumers and production registration before removal or migration. |

Local registration: jquerycore, contextgroups, assignment, contextcontent and mcqtests are registered. ext, jquery, activitystreamer, contextinstructor, practicals, triplestore and langadmin are unregistered. Core module files exist under core_modules even when absent from packages; registration and filesystem presence must not be conflated.

## jQuery findings

`skinjavascript::getJQuery()` loads jquerycore unless SUPPRESS_JQUERY is set; it reads JQUERY_CORE/UI_THEME configuration and session plugin selections. If jquerycore is unregistered it falls back to the old jquery wrapper, whose default is 1.5.2. jquerycore retains multiple versions, including 1.10.2, plus jQuery UI and a plugin dependency catalogue. The coreloader default sorts dot-stripped version keys; replace that mechanism only with explicit compatibility tests, not as an incidental audit edit.

Source candidates span File Manager, shared HTML elements, filters, navigation, registration, Discussion, CMS administration, forms/formbuilder, messaging, OER, galleries and other modules. Counts from broad searches include vendored code and comments and should not be reported as counts of active features. A global library swap is not yet justified.

Before migration, capture actual scripts and console errors on representative local screens, configured versions, session plugins and duplicate loads. Inventory inline jQuery, dollar aliases/Prototype conflicts, UI widgets, Ajax/form plugins, editor plugins and drag/drop. Test save/retry/session-expiry and keyboard/narrow-screen behaviour per migrated workflow.

## Remaining Flash/Java candidates

- PICTURESHOW filter loads Runway Flash: replace with native image presentation after checking stored shortcode contracts and file access.
- SWFUpload helper/bundle remains; initial PHP caller search found no external helper invocation. Complete dynamic/resource and stored-content tracing before pruning.
- File Manager presentation conversion still contains SWF paths and a JODConverter Java command. Distinguish server-side document conversion from obsolete browser plugins; preserve originals and authorised delivery.
- Unregistered local langadmin offers ChisimbaLangTranslator.jar for download. Trace production use and current browser language-admin replacement before retiring the download/client.
- TinyMCE Flash integrations and other bundled plugin examples need a separate asset/configuration check.

## Bounded implementation order

1. Prove old MCQ, assignment and context-group resources unreachable, remove complete dead asset groups and add retirement checks.
2. Migrate live context-content reports to shared native UI, preserving permissions, filters, paging and exports; exercise actual routes locally.
3. Audit production registration and decide remaining legacy module migrations, preserving Activity Streamer for later revival.
4. Replace PICTURESHOW and document-preview plugin paths with native presentation through existing file-access services; separately retire unused upload/client assets.
5. Consolidate jQuery loading only after page-level dependency evidence, then migrate feature groups with browser coverage. Choose any temporary supported vendor version using current upstream documentation at implementation time.

No runtime files, site configuration, databases or deployed sites were changed by this audit. Browser regression testing is required for implementation; this document does not claim those future migrations are tested.

## 14 October: obsolete asset removal

Removed the four contextgroups ExtJS resources (lecturer, student, users, members), assignment/resources/addedit_assignment.js, and the unreachable MCQ choosequestiontype2 template plus chooseqtype, mcqdb and calculateQ scripts. Repository caller searches found no remaining resource loaders; the old MCQ action retains its redirect to native banks. The native assignment template and group-service member renderer remain. No registration/schema change is required for unused asset deletion.

Retirement guards pass in both repositories. Membership authorisation, role exclusivity and lecturer-assignment journey checks pass, as do MCQ bank-question, question-button and overview-management checks. The old control_panel_members_contract_test fails identically against HEAD before removal: it expects lecturer-specific variable names and management-link/title behaviour superseded by the current all-role block. This is pre-existing test debt, not a passing check.

Local Chrome smoke checks passed for the MCQ overview, question banks, course membership page and assignment creation form. The old MCQ picker URL preserves the active-test edit guard; the native bank screen loads through its current link. No records were saved or changed. Production deployment is not included. The next implementation slice remains the reachable context-content reports; neither ExtJS runtimes nor jQuery have been removed.

## 14 October: remaining module decisions

Context-content activity reports have been migrated to native UI (modules commit `2021c0fcd`), and duplicate Context Instructor retired (`90fec4755`). Derek approved renaming Practicals to `practicals__PAUSED`; its source and historical data are retained. Triple Store and Mentors are explicitly preserved for future revival, alongside Activity Streamer. Their current unregistered state does not authorise deletion. See the modules repository's `docs/legacy-module-revival.md` for revival and deployment conditions.

The next ExtJS work is to migrate the preserved Triple Store and Activity Streamer interfaces, with an associated Mentors compatibility review. ExtJS runtimes remain until these callers and the shared wrappers have been addressed. These decisions do not activate modules or authorise production deployment.

## 14 October: Triple Store native administration

Triple Store 0.012 now uses native, administrator-only forms and shared Help,
with bound dbTable queries, POST/CSRF protection and conflicting-edit checks.
Its old ExtJS resources and Ingrid/Editable helper are removed. Mentors storage
compatibility is tested; the Mentors module remains dormant. Triple Store was
installed locally for testing and its disposable records removed. See the modules
repository's `triplestore/README.md` for route changes and validation limits.
Activity Streamer remains the next preserved ExtJS caller; shared runtimes have
not yet been removed. No production deployment is included.

## 14 October: Activity Streamer native browser

Activity Streamer 0.045 replaces all six retained blocks and the old grids/pop-ups
with a shared native browser. Site events are administrator-only; current-context
reports require administrator/instructor access. Counts, JSON and blocks share
that policy. Event recording is opt-in and remains off locally; obsolete external
hub publishing is retired. A guarded catalogue migration preserves numeric author
values while allowing current string user IDs. Local policy, PDO, catalogue and
browser checks passed; see `activitystreamer/README.md` in modules for scope and
validation limits. No production deployment is included.

The next step is the final shared ExtJS wrapper/runtime caller audit, accounting
for paused source separately. The shared libraries have not yet been removed.

## 14 October: shared ExtJS retired

The final caller audit and local smoke tests passed. Shared ExtJS distributions,
loaders and theme assets are now removed, together with one additional unreferenced
MCQ grid script in modules. See `extjs-retirement-20261014.md` for evidence,
validation limits and the mandatory coordinated framework/modules deployment.
Production remains unchanged. Paused modules require native UI migration before
revival; retaining their source does not retain an executable ExtJS runtime.

## 14 October: native document remnants

Completed the targeted SWFUpload, Runway/PICTURESHOW, document-to-Flash,
Java presentation picker, desktop translator and TinyMCE Flash-plugin slice.
See [evidence and limitations](native-document-remnants-20261014.md).
Source/local only; remaining ZeroClipboard/OpenFlashChart/optional jQuery assets
must still be traced before claiming universal Flash retirement.

## 14 October: Flash clipboard/chart utilities

Removed ZeroClipboard, OpenFlashChart and the jquerycore Flash plugin after
tracing their callers. The incorrect Context statistics demo now links authorised
teaching staff to the maintained content activity report; old chart endpoints
return 410. See [tests and the remaining plugin map](flash-utilities-retirement-20261014.md).
The remaining 12 optional jquerycore plugins are retained for complete caller
migration, including dynamically selected wrappers and duplicate legacy stacks.
