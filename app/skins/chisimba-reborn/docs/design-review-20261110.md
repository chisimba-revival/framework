# Shared visual review — 10 November 2026

Status: local design review, not approved for production. Both repositories use
`design/shared-visual-review-20261110`; merge into the common development branch
after review and before any common-source deployment. Production manifests have
not changed. Existing private REST overlays are excluded from this work.

## First pass

- Shared editorial typography, surfaces, sectioned forms, field groups, action
  bars and image/story compositions; native CSS with no new library.
- Learn-the-Birds: warm surfaces and serif headings. KengaSolutions: cooler
  surfaces and stronger sans-serif hierarchy. Identity lives in canvas tokens.
- User editor: shared fieldsets, guidance, narrower identity fields, consistent
  input heights and mobile grid behaviour. Removed competing module form CSS.
- SimpleBlog: unboxed metadata and settings, clearer title field and grouped
  actions. Existing form fields, CSRF, versioning and publishing rules preserved.
- Static workshop uses the actual shared CSS. Canvas, example and viewport
  selectors compare three samples. Width changes preserve typed sample values.
  Public page content is illustrative, not installed as a homepage.

Open `design-showcase.html` beside this file through the running local site.
No separate preview server is required. The sample forms never submit to a
backend; photography is loaded from public Learn-the-Birds image URLs.

## Validation

- PHP lint: user editor, shared page template and SimpleBlog editor.
- Useradmin country policy, password-manager autofill and edit-record contracts.
- SimpleBlog publication-date and video-caption excerpt tests.
- Skin ownership, stylesheet cache version and descendant component contracts.
- Browser: both canvas directions, all three specimens at 320/390/768/1280px
  (24 combinations), no specimen horizontal overflow. Default-canvas form also
  reviewed at phone/tablet width. Keyboard Tab retains a visible focus outline;
  sample save reports that no data was saved; resize preserves typed values.
- Real local user editor loaded with registered guidance; mobile grid overflow
  fixed. No users or posts were saved during checks.

## Limits and follow-up

The local EcoTraining clone did not have SimpleBlog registered, despite its code
being present. The actual editor loaded but had missing language labels. A normal
Module Catalogue installation attempt failed with SQLSTATE 42000. Do not call the
live publishing journey verified: investigate that local installation error
before testing draft/publish, media chooser and Help end to end. No production
installation or content changes were attempted. Useradmin alone was upgraded
locally from 1.972 to 1.973 through its normal update control.

This is the first shared design pass, not a claim that every legacy module has
been redesigned. Next: review the two identities, resolve the local publishing
setup, then apply the accepted components to remaining forms and real public
page compositions. Keep that work on shared source; canvases remain branding.
