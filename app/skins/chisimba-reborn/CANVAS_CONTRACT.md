# Chisimba Reborn skin and canvas contract

`chisimba-reborn` is the maintained rendering system. It owns page templates,
layout behaviour, component primitives, interaction states, responsive rules
and accessibility behaviour.

Its named canvases provide identity and deliberately small presentation
overrides:

- `chisimba` is the standard Chisimba identity.
- `chisimba-classic` restores the historic earth, ochre and charcoal palette.
- `kenga-learn` supplies KengaLearn colours and its logo.
- `my-hair-planner` supplies the green and gold logo with a light banner.
- `_default` remains the safe compatibility fallback.

A normal branding canvas may contain only semantic colour, typography and
identity tokens; brand assets and metadata; and exceptional layout rules where
the canvas intentionally changes page composition. It must not copy skin
components or the shared page template. UI primitives and fixes belong in the
root skin so every canvas receives them.

## Selection and compatibility

The existing canvas service stores the site preference as
`canvas_preferredcanvas`. Contexts may continue to store a canvas identifier
in their `canvas` field.

The former `kenga-learn` skin remains temporarily as a compatibility entry
point for installations whose `KEWL_DEFAULT_SKIN` still names it. It delegates
to `chisimba-reborn` and selects the `kenga-learn` canvas. New and migrated
installations should use `KEWL_DEFAULT_SKIN=chisimba-reborn` and select branding
through the canvas preference.

## Adding a canvas

Create `canvases/<identifier>/` with `canvas.json`, `settings.php` and a small
`stylesheet.css` importing `../_default/stylesheet.css`. Keep canvas-owned
assets below that directory. Add a contract test for any exceptional layout.

A canvas may provide `favicon.png` at the root of its directory. Use a square
PNG with a simple mark that remains recognisable at 16 and 32 pixels. When the
file is absent, the skin's `favicon.png` is used as a compatibility fallback.
The rendered favicon URL includes the file modification time so a replaced
icon is not hidden by a stale browser cache.

## Banner identity tokens

Canvases may set `--chisimba-banner-background`,
`--chisimba-banner-foreground`, `--chisimba-banner-name-shadow` and
`--chisimba-banner-icon-color`. The shared skin retains its existing gradient,
inverse text and icon colours when these are absent. These tokens allow light
banners without changing the text colour of primary buttons. Logo dimensions
and the logo image remain canvas identity tokens; do not copy banner selectors
or layout rules into a branding canvas.

## Editorial identity and form tokens

The shared `components/editorial.css` loads after the base canvas and before the
selected canvas. Canvases can set `--chisimba-font-family-heading`,
`--chisimba-heading-weight`, `--chisimba-heading-tracking`,
`--chisimba-editor-radius` and `--chisimba-image-radius`, alongside the existing
semantic colour/surface tokens. Form mechanics, focus states, spacing and
responsive breakpoints remain in the shared skin.

Learn-the-Birds explores a warm field-journal direction with serif headings;
KengaSolutions uses stronger sans-serif headings and cooler surfaces. These are
review directions, not separate application implementations.

Compact banners opt in through `--chisimba-banner-min-height`,
`--chisimba-banner-min-height-mobile` and `--chisimba-banner-padding-block`.
Logo height tokens preserve the asset's aspect ratio. The shared utility rail
reserves space in normal flow and wraps instead of covering the logo.
Light banners can set `--chisimba-banner-pill-background` and
`--chisimba-banner-pill-border`; pill text uses the banner foreground.
Existing role/journey icon colours and semantic status tokens retain their meaning.

Learn-the-Birds retains its established blue primary/action palette; the warm
neutral surfaces and serif headings are independent presentation choices. Its
existing white wordmark is rendered dark by a CSS brightness filter on the light
banner, without changing the source asset.

## Chisimba.com canvas review

Chisimba.com selects `chisimba-classic`, not the `_default` fallback or the
blue `chisimba` identity. Its revision keeps the recorded earth/ochre action
palette and original logo, and adds warm stone surfaces, strong system-font
headings and a compact charcoal banner. All changes are identity tokens; forms,
responsive layout, banner pills and status meanings remain shared skin behaviour.

`docs/chisimba-canvas-preview.html` is a local visual specimen using a snapshot
of the public welcome block and two stories from 10 November 2026. It omits the
live video and mailing-list form and is not a homepage replacement or database
clone. Links lead to the public sites; the banner selector changes only the
specimen. The workshop also offers this canvas for sample forms and editors.

Checked public content with administrator pills at 320, 390, 768 and 1280px,
plus the user-form and expanding-title editor specimens at phone/desktop widths.
No horizontal overflow or clipped title was observed. Skin ownership and header
journey contracts pass. This revision remains on a design branch pending review;
production and the Learn-the-Birds canvas are unchanged.
