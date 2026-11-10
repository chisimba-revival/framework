# Chisimba Reborn

Chisimba Reborn is the modern reference skin for the restored Chisimba
framework.

It is the single maintained modern UI implementation. Brand colours, logos
and other identity choices live in named canvases. See
[CANVAS_CONTRACT.md](CANVAS_CONTRACT.md) for the enforced ownership boundary.

## Meaning and visual identity

“Chisimba” is the Chichewa word for the wooden framework used to build a
traditional African house. Chisimba is the framework; a deployed application
is a particular house constructed with that framework.

The default canvas therefore retains the original Chisimba logo and derives
its design tokens from the original earth, ochre, gold and blue palette.

## Architecture

```text
Skin
    renders
        Canvas
```

The skin owns templates, reusable design tokens, typography, common component
styles and rendering behaviour. A canvas supplies contextual presentation.

The initial `_default` canvas preserves all three historical content regions
and arranges them responsively with CSS Grid.

## Consistent action heights

Use `chisimba-form-actions--equal` alongside `chisimba-form-actions` when a row
combines text buttons, compact Help or nested submit forms. It stretches adjacent
controls together with a 2.875rem minimum height. Verify their rendered heights
and alignment at desktop and mobile widths, including wrapped labels.

## Banner and focused workspaces

The banner begins at the top of the viewport. The skin removes the canvas and
legacy container's top spacing while retaining their side and bottom gutters.

A module can apply `chisimba-focus-surface--active` to its working surface and
`chisimba-focus-open` to the body to fill the viewport. The module must preserve
its forms, make surrounding controls inert, provide a visible exit and Escape
handling, and restore focus, scroll and prior inert state when leaving. The skin
owns dimensions, background and stacking; branding canvases need no overrides.

## Adjacent action buttons

Keep related actions in one row when space permits, with the primary action first
and secondary actions beside it. Use `chisimba-cluster` for a compact wrapping
button/link group, or `chisimba-form-actions` for a form action row. Their shared
gap separates targets horizontally and vertically when wrapping or stacking.
Avoid unnecessary rows, empty containers and oversized spacing; preserve usable
targets and clear separation on narrow screens rather than compressing controls.

Text buttons and icon-only buttons in an action row must have matching rendered
heights and aligned edges, including when text wraps on smaller screens. Check
actual browser dimensions at desktop and mobile widths; sharing a button class
alone does not establish consistent sizing. Preserve visible focus and accessible
names for icon-only actions.

## Task-oriented forms

Use `chisimba-publishing-layout` for an editable main column and a narrow action /
guidance panel. Group related fields into compact form cards. Optional groups can
use semantic `details` with `chisimba-form-card` or `chisimba-form-disclosure`.
Use `chisimba-form-field--brief` on short-answer fields so their textarea respects
its declared `rows` instead of the long-answer minimum. Keep long descriptions
full-width; pair genuinely short related fields with `chisimba-form-grid`.

For an inline editor among action buttons, place a semantic `details` element
with `chisimba-action-disclosure` first in a `chisimba-cluster`. When open it
occupies the full row, so subsequent actions wrap beneath it in DOM order.
Compose its form with `chisimba-form chisimba-flow` and explicit field labels;
flow spacing also separates dynamically appended draft notices from Save.

## Tag clouds

The utilities `tagcloud` service renders `.chisimba-tag-cloud` as a semantic,
wrapping list. Five `chisimba-tag-cloud__weight-*` classes express relative
frequency; this root skin owns their sizes, spacing and link colour. Keep tag
links underlined and keyboard focus visible. Do not add per-cloud inline CSS or
age-based fading in a canvas. Tag selection, counts and access remain the owning
module's responsibility.

Native checkboxes and radios retain compact control dimensions and align with
adjacent text. Text-field height/padding rules must exclude both input types.
The shared radio helper emits `.chisimba-choice` wrappers: each input and associated
label align on their first baseline, and wrapped labels stay in the text column.
Keep native inputs, label associations and keyboard behaviour; avoid module-specific
position offsets to compensate for stretched controls.

## Public entry screens and mobile review

The page template declares a device-width viewport without restricting zoom.
Anonymous pages use `chisimba-public-shell` to remove nested desktop gutters on
small screens. Their banner offers a direct Login link below 60rem; it uses the
normal security route and validated return destination. Public content and
sidebar blocks retain their configured order. Both embedded and dedicated login
forms keep visible labels, native autocomplete and touch-sized controls.

Open [the public entry preview](docs/public-entry-preview.html) through a local
installation to compare the actual home, sign-in and password recovery
screens at 320, 390, 768 and 1440 pixels. The preview uses the current canvas and
real routes, not duplicated application markup. Forms are live: do not submit
password recovery unless an email is intended. Changing preview size or screen
does not change site settings. Check keyboard focus, long headings, navigation
expansion, image sizing and horizontal overflow before releasing skin changes.

## Design workshop and sectioned forms

Open `docs/design-showcase.html` through the local web server to compare the
maintained CSS across canvases at 320, 390, 768 and 1280 pixels. It is a static
specimen: sample forms do not save, publish, send mail or change accounts. Images
are public Learn-the-Birds URLs and need network access. The shell and sample
content are illustrative; this is not a replacement for testing actual modules.

`chisimba-form--sectioned` uses semantic fieldsets and legends. Group related
fields with `chisimba-field-grid`; its identity modifier gives titles a narrower
column. `chisimba-field--full` spans the grid, `chisimba-field--short` constrains
short identifiers, and `chisimba-choice-row` keeps checkbox labels horizontal.
Use `chisimba-section-description` for brief section guidance.

`chisimba-editor-surface`, `chisimba-editor-meta`, `chisimba-title-field` and
`chisimba-editor-section` support a less boxed publishing workspace.
`chisimba-action-bar` groups wrapping actions with a dividing rule.
`chisimba-feature`, `chisimba-story-grid` and `chisimba-story` are reusable
editorial composition primitives; they do not alter existing page content.

The first consumers are the native user editor and SimpleBlog post editor.
Existing submission, permissions, CSRF and composition contracts are unchanged.

Title fields can use a `textarea` with `rows="1"` and `data-auto-expand`.
The shared autoexpand helper fits its height on input, width changes, font load
and form reset. Keep the normal label, name, required and maxlength attributes.
Without JavaScript the textarea remains manually resizable.
