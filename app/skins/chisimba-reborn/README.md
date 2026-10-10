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
