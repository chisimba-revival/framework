# Native My Profile — 14 October 2026

User Details 2.012 removes its direct jquerycore dependency. Profile success feedback,
photo update/reset and optional school lookup no longer use jQuery dialogs, Ajax
helpers or autocomplete. The skin still loads global jQuery and jQuery UI for other
callers; this does not complete the wider jQuery migration. Course block editing is
next in the inventory plan.

Photo mutations require authenticated POST and shared, login-bound CSRF tokens.
The current user is authoritative; submitted user IDs are ignored. Only readable
images owned by that user in their personal File Manager tree can become public
profile pictures. Course files and other users' files are rejected. The shared
picker can still start in course scope: select **My files** for profile photos.
Supported raster inputs are JPEG, PNG, GIF and WebP, at most 10 MiB and 16 million
pixels. Both PNG derivatives are staged before replacement, with backups and a
per-user lock. Processing failure preserves the existing derivatives. Reset uses
the canonical security service. Legacy GET reset and Ajax session-reset routes
return 410.

Details POSTs also require CSRF. Rejected tokens and interrupted saves retain
non-password entries; passwords must be entered again. A GET validation request
does not overwrite a saved draft. Interrupted responses are never automatically
replayed. Contextual Help describes the workflow and recovery.

Optional school modules are absent and unregistered locally. Provider-contract
fixtures cover lookup result shaping and allowed grade membership changes; native
client fixtures cover stale results, clearing IDs on edits, empty results and
Escape. Grade writes follow the canonical administrator-only group service and
allow only names obtained from the grade repository. The optional providers' SQL,
schema and complete installed workflow still require review before revival. They
have not been certified by this migration.

Validation:

- PHP syntax and JavaScript syntax; Git whitespace checks.
- `native_profile_actions_test.php`: methods, authentication, CSRF recovery,
  submitted identity, optional providers and restricted grade writes.
- `native_profile_details_test.php`: method guard, input shapes, secret-free drafts
  and interrupted save handling.
- `native_profile_photo_test.php`: filesystem ownership/read policy/confinement,
  malformed input, failure during the second derivative, cleanup and reset.
- `native_profile_photo_pdo_test.php`: local PHP 8.5, actual PDO file/folder records,
  canonical read policy, shared GD resizing and shared reset. Synthetic identity;
  database fixtures roll back and output files are removed.
- `native_profile_client_test.js`: school request ordering, selection invalidation,
  empty results, Escape, duplicate submission and interrupted/expired responses.
- Existing author biography regression checks.
- Local Chrome: profile rendering, rejected empty photo request, native picker,
  quick/full Help and Escape; desktop and 390-pixel viewport inspection. The shared
  off-screen Help drawer contributes a small narrow-screen overflow (8 pixels);
  profile controls themselves remain within the viewport. No new PHP warnings or
  deprecations found in the final local log window. The initial browser check found
  embedded JSON intercepted by the content filter; ordinary data attributes fixed
  it and subsequent requests worked.

Module Catalogue update ran locally with installation hooks and language items.
No production deployment or actual account/profile mutation was performed.
