# Bookmarks / Statusbar assessment — 16 October 2026

Review requested by Derek after pausing Forms. Derek approved preserving both as __PAUSED. Original source has been renamed
without modification; no site data was removed. These are separate from Contextcontent/SCORM
course-page bookmarking, which must remain unchanged.

## Responsibilities and dependencies

Bookmarks manages personal folders and saved internal page links, with a toolbar
entry and reusable block. It declares jquerycore and buddies dependencies.
Statusbar provides a floating bar for bookmarking, messaging, online users and
calendar/content/gallery alerts. It depends on Bookmarks and activitystreamer,
and loads additional buddies, messaging and calendarbase services. Both use
jquerycore dialogs and jQuery client code.

Shared toolbar classes tools and toolbar_elearn conditionally load these modules
when registered and authenticated. Any pause must neutralise these hooks so stale
registration cannot trigger missing classes. No other active external object
callers were found in the focused PHP trace. Text matches for course bookmarks
are different functionality, not module dependencies.

## Security findings requiring a revival, not a dialog swap

- Neither controller has explicit HTTP-method/CSRF guards for its mutations.
- Bookmarks folder/bookmark updates and deletions use caller-supplied IDs without
  ownership predicates. Folder subtree traversal/deletion is not scoped and
  transactional. Multiple repository reads interpolate IDs into SQL.
- Bookmarks accepts a target folder without verifying ownership; context switching
  is delegated from a request parameter and needs policy review.
- Statusbar updates bridging-alert rows by ID alone; message/alert operations need
  review through their owning services. Settings and message sends lack route-level
  POST/CSRF protection. No test message has been sent.
- Both controllers derive callable action names with method_exists instead of a
  fixed action allowlist.

## Read-only usage evidence

Receipts are in work/bookmarks-statusbar-trace-20261016 in the parent workspace.
The scan reports module registration, counts of the four owned tables and matching
selected text/relevant varchar columns; it never prints stored content. Search
terms cover module URLs, resource paths, ops classes and named Statusbar blocks.
It is not proof of absence of arbitrary encoded links, external integrations or
all possible placement formats. Confirm all site receipts before pausing.

## Recommendation

Preserve both as __PAUSED unless personal Bookmarks has a concrete near-term use.
If Bookmarks is revived, retain folder/link identities but migrate its access
policy, repository transactions, native editing and Help together. Pausing the
floating Statusbar must not remove current notifications, course bookmarks,
Calendar, messaging or Activity Streamer themselves.

Completed scan: all six production sites and local Chisimba have neither module
registered, none of the four owned tables, and zero matching stored columns.
No production or local runtime data was changed.

## Completed pause

Both source directories preserved under __PAUSED, with revival notes. The shared
tools hooks return false without loading services, including with stale module
registration; toolbar_elearn no longer loads either module. Regression checks for
paused hooks, notification visibility and site navigation pass. Local Chrome
homepage and notification toggle pass with no console warnings/errors. No
production deployment. Course bookmarking and maintained notification services
are unchanged.
