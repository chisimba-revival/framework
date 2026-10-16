# CMS revival assessment — 16 October 2026

## Recommendation

Technically feasible, but a focused rebuild of the CMS administration workflows
is preferable to restoring the old jQuery interface. Keep CMS separate from the
current library cleanup and disabled on working sites. This is a source assessment,
not certification of the CMS security model or a delivery-time estimate.

CMS is worth reviving if the intended site needs a structured hierarchy of pages,
section-specific editing/access rules, publication scheduling and custom navigation.
For a small set of public pages, first test whether maintained Site Pages plus
Contentblocks already meets the need. Do not build a second page editor solely to
remove a legacy dependency. The specific intended CMS use remains to be defined.

## What is worth retaining

- Content and section identities, hierarchy and links where existing data exists.
- Separate public display and administration responsibilities.
- Explicit public/private access and delegated section/content editing concepts.
- Draft/published state, publication dates, metadata and navigation ordering.
- Useful block composition and short-path capabilities through maintained services.

The original implementations of those concepts are not authoritative security
policies. Validate the intended semantics before preserving behaviour.

## Why a cosmetic migration is insufficient

CMS Admin's controller, cmsutils and dbsecurity alone total about 11,900 lines.
UI, request processing, SQL, templates and permissions are heavily interleaved.
Boxy, tabs, grids, old menus and Cluetip are presentation dependencies; jQuery
itself is replaceable, but removing it does not fix the server contracts.

The permission checkpoint repaired one route family only. Many other mutations
still need explicit persisted authorisation, POST/CSRF, scalar input validation,
checked writes and concurrency controls. Public reads, previews, feeds and file
access need the same policy review. Raw content handling and template persistence
also require scrutiny. Missing declared feed/blockalicious dependencies and older
service calls block an ordinary revival; the previous isolated catalogue attempt
did not complete. Do not restore retired libraries merely to satisfy that list.

## Suggested separate project sequence

1. Define a representative site and essential page hierarchy/publishing workflow;
   compare it with Site Pages/Contentblocks and identify only the missing features.
2. Map the CMS data and access rules, retain IDs/URLs and establish migration and
   rollback fixtures. Verify whether any data needs importing on the target site.
3. Implement a small vertical slice: section/page list, native editor, draft preview,
   publish/unpublish and public reader, using shared editor, sanitiser, file access,
   dbTable, permissions, Help and skin primitives.
4. Test anonymous/member/editor/admin/revoked access, direct routes and files;
   fresh/repeated installation, stale edits, failed-write rollback and browser
   keyboard/mobile/draft recovery. Add only justified menus/scheduling afterwards.
5. Remove replaced legacy branches and their last library callers, then stage a
   reviewed release. Flags, bulk email and export are separate optional workflows.

Current course bookmarks, notification services, Calendar and Activity Streamer
are not part of this proposed CMS rebuild.
