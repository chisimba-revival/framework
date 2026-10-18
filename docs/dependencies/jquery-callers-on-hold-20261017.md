# Remaining jQuery callers: paused modules and ePortfolio — 17 October 2026

Derek authorised preserving Wall and SimpleGal as `wall__PAUSED` and
`simplegal__PAUSED`. All original source/assets remain; no database content was
removed. Both were unregistered locally and on all six production sites, with
no module tables. SimpleGal had no stored matches. Broad Wall searches matched
ordinary content/language/log text; narrower route, JSON-module and block-marker
checks found no stored uses outside logs/language. Source tracing found no active
external consumers (one SimpleGal Wall reference was commented out).

The module pauses are source changes, not a production deployment. Reusable
blocks and endpoints must receive an ownership/access, POST/CSRF, persistence and
native-client review before either module is reactivated. Their PAUSED.md notes
record the relevant risks. The archived SimpleGal publishing-retirement test
continues to exercise the preserved controller at its new path.

## Old remote Blog filter

`parse4blog` was a separate browser-side jQuery loader for the paused Blog
publisher, not the already retired XML-RPC `parse4blogpost` or supported SimpleBlog.
It interpolated untrusted filter arguments into JavaScript and imported remote
HTML. No `[BLOG:]` references were found in the inspected stored-content columns
locally or across the six sites. Filters 1.572 leaves input unchanged and loads no
services, scripts or remote content. Preserve legacy markup rather than rewriting
stored content. This follows the earlier retirement of the old Blog publishing
integration.

Validation: actual filter tests with ordinary, Unicode, malformed and hostile
legacy markup; archived publishing GET/POST/case retirement tests; PHP syntax and
whitespace; installed local catalogue update and inert-filter rendering with
warnings treated as errors. Paused module paths verified locally. This batch does
not alter a live UI workflow; no new browser acceptance claim is made.

## ePortfolio: revival on hold by request

Keep ePortfolio and all its data. Do not mark it as retired or silently remove its
import. It is currently unregistered with no module tables or stored references
on the local and six audited production installations. That is bounded evidence,
not permission to discard it or proof that its dormant features are safe.

Its remaining jQuery caller is the old IMS import form. The import/check routes
lack explicit ownership/POST/CSRF checks, and the approximately 700-line importer
reads manifest-supplied paths and performs many writes without one transaction.
Its XML service is approximately 3,000 lines; the controller is approximately
2,900 lines. Replacing the JavaScript alone would not make this workflow safe.
No ePortfolio implementation was changed in this batch.

Recommended revival order:
1. Agree the EcoTraining evidence/reflection/competency/sharing workflow; inventory
   reusable data models and identify which old features are actually required.
2. Establish canonical owner, viewer and assessor permissions, private file
   delivery, POST/CSRF, draft retention, revision checks and transactional writes.
3. Modernise core portfolio creation, evidence, reflection, organisation and
   controlled sharing with native controls, accessible Help and PHP 8 checks.
4. Rebuild IMS import only if needed, with owned file access, bounded/network-free
   XML parsing, path containment, validation/preview and atomic rollback. Preserve
   data and test malformed files and mid-import failure.
5. Complete fresh/update installation, negative permission, PDO and browser tests
   without jQuery before activation or deployment.

The importer remains an explicit jQuery dependency. Global jQuery loading remains
on. Before disabling it, migrate this caller or provide a deliberately reviewed
module-scoped compatibility path; do not confuse pausing the work with completing
that migration. CMS/CMS Admin are also deferred. Other filters, Blogcomments and
smaller assets still need tracing.
