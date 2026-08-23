# Chisimba Reborn skin and canvas contract

`chisimba-reborn` is the maintained rendering system. It owns page templates,
layout behaviour, component primitives, interaction states, responsive rules
and accessibility behaviour.

Its named canvases provide identity and deliberately small presentation
overrides:

- `chisimba` is the standard Chisimba identity.
- `kenga-learn` supplies KengaLearn colours and its logo.
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
