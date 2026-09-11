# Course marketing pages — 11 September 2026

Implementation is on feature/author-biographies, intended to accompany the author/profile work in the next KengaLearn update. No production deployment performed.

## Author journey

Open the course control panel → Marketing page (also linked from the About block's author controls). Write an introduction and optional audience, outcomes and public outline. Outcomes/outline use one item per line. Add an optional HTTPS YouTube/Vimeo promotional video. Save a draft and preview it, then explicitly publish. The title, course image, description, author biographies and linked product pricing remain authoritative in their existing systems.

A published landing page has a stable context marketing URL, native skin hero/actions, optional video and automatic author biographies. OpenGraph title, description, absolute image and canonical URL support sharing. Existing course-entry card actions remain unchanged. Learn more is secondary and appears only for non-members when a page is published. Administrators and assigned authors are treated as members for this display decision.

Product/access decisions reuse coursecatalogue's existing resolver: paid course Buy now links to its product catalogue/checkout journey; free/public courses enrol through canonical course entry; existing members or entitlement holders continue learning. Tier membership products retain their distinct membership action/price rather than masquerading as a course purchase. Manual admission/unconfigured purchasing explains the next step without a broken buy button. This implementation does not bypass login, payment or access grants.

## Data and boundaries

New tbl_context_marketing table has a unique contextcode and plain-text marketing fields. Publication defaults off. Drafts can be previewed only by course authors/site admins; anonymous requests return404. Unpublished courses suppress public marketing even if the page's flag is set. Publishing is explicit permission to expose the course description; lesson bodies/content trees are never read by this feature. The outline is authored public copy, not automatic publication of private chapter metadata.

Writes check the selected course's author role, not the current session's unrelated course. CSRF tokens are bound to the selected course via a bounded hash context. Video embeds accept only known YouTube/Vimeo URL shapes. User-supplied text is escaped. External biography links remain omitted on course pages.

## Upgrade and release

Upgrade userdetails to2.011 and context to2.053 using modulecatalogue; deploy their framework source and skin CSS together. Complete upgrades before serving cards which consult the new tables. The context register contained a pre-existing malformed two-part help language entry; this has been corrected so language registration completes. On the local environment the schema/version portion ran before that existing error was diagnosed, so context language terms were subsequently refreshed with modulesadmin::moduleText('context','replace'). On production use the corrected registration file from the outset and verify schema, versions and language terms before opening traffic.

Take normal KengaLearn backups and preserve all course/Knowledge Map data. Deploy the accumulated author biography, profile and marketing changes together at the next approved update. This file is not a claim production deployment has occurred.

## Verification

- PHP course_marketing_test.php: draft/publish/unpublish, published-course boundary, author permission, video URL validation, product price and Buy now, free enrolment, existing member and entitlement Continue learning.
- Existing course catalogue, management-card and tiered-admission contract tests pass.
- Local browser: draft saved/reloaded, published and previewed with live course title/image/description, member Continue learning CTA.
- Anonymous HTTP: draft404, published200; actual catalogue contains the correct Learn more route.
- Actual response has one correct OpenGraph title/image/description and the correct share URL (uses skin variables, not duplicate defaults).
- Temporary testing106 marketing data removed after tests; existing course data unchanged.

Before production: finish narrow-screen/keyboard review and follow a real configured product's sign-in/checkout path up to, but not submitting, a payment. No real payment was made during these tests. Video URL mapping is tested; external playback depends on the provider and recording availability.
