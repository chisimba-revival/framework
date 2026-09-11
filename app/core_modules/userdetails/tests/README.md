# Author biographies

Implemented in userdetails 2.011. The editor is available through My Profile → the configured `[-author-] biography` label (`action=biography`). A biography is plain text, up to 6,000 characters, plus up to five labelled HTTPS links. It reuses the account photo and supplies an icon placeholder where absent. Only the current authenticated user can save their own biography; writes require a CSRF token and redirect after success.

Biographies are intentionally separate from private account details and the optional legacy `profiles` module. They are stored once per logical user, with a unique database key. Course author association continues to use `usercontext::getContextLecturers`; no second membership system was added. `authorbiographyservice::forCourse` supplies deterministic, deduplicated author data. `authorbiographyrenderer::forCourse` renders biographies beneath the existing About Context description. External links are omitted from course presentation. Missing biographies are reported only to the course teaching team/admin; owners receive a completion link. The future Learn more page can reuse the same renderer.

## Upgrade

Use the normal modulecatalogue patch/update mechanism to upgrade userdetails to 2.011, creating tbl_userdetails_biographies and loading its language terms before serving the updated course About block. Deploy the framework change and skin CSS together. No production deployment has been performed. No account or course membership data migration is needed; do not copy private account descriptions or legacy profiles automatically into public biographies.

## Verification

Run `php app/core_modules/userdetails/tests/author_biography_test.php` from framework. Tests cover field limits, unsafe URLs, missing link labels, ownership ignoring submitted user IDs, anonymous service rejection, course author deduplication/order, output escaping and suppression of external links on course cards.

Local PHP8.5 verification on 11 September 2026:
- Module upgrade from 2.010 to 2.011 succeeded.
- My Profile entry point and profile-photo navigation inspected in Chrome.
- Biography with paragraphs and an external link saved, previewed and reloaded successfully.
- Course home automatically displayed the current assigned author's biography.
- Two-author rendering checked against the local test course using temporary biographies; both appeared and external links were absent.
- Temporary biographies removed after verification.
- Anonymous editor request checked against the login boundary.
- PHP syntax and Git whitespace checks passed.

Still separate work: Learn more landing page, product CTA/price integration and a fuller public author-profile route. Responsive skin rules are included; a dedicated narrow-screen browser pass remains advisable before production release. Existing My Profile photo selection is reused, not replaced by a parallel upload system.

The signed-in account block also exposes My Bio immediately after My Profile. Empty biographies have an accessible “Biography needed” alert icon; completed biographies retain the link without the alert. Both states use authorbiographyservice::needsBiography. The link position, missing-state icon and destination were verified in the local browser; regression coverage checks empty/whitespace versus completed biography state.

## Profile layout and photo round trip

The biography workspace uses the skin's wide form card with a two-column editor/saved-preview layout, stacking below 850px. My Profile uses the same workspace and shared section navigation, with personal fields in a semantic form grid and the existing photo picker alongside. The legacy layout table has been removed from the personal details form; account validation and persistence remain the existing implementation. Password controls are disclosed separately.

The photo link targets the profile photo section, which provides Return to My Bio. Unsaved biography text and link fields are retained in tab-scoped sessionStorage, keyed by user and application path, for up to one hour and cleared after successful save. This is progressive enhancement; server-side saves remain authoritative. Browser verification confirmed the unsaved-text round trip and the original text was restored without saving over the user's biography. A live saved preview remains explicitly labelled as saved, not as an unsaved draft preview. The portrait refreshes through the existing image-update response.

PHP and JavaScript syntax, existing biography regression tests and whitespace checks passed. Desktop profile fields and photo navigation inspected in Chrome. No user account changes, photo changes or production deployments were made during this layout test. School-specific fields remain supported but have not had a dedicated browser pass; narrow-screen browser verification remains pending before release.
