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
