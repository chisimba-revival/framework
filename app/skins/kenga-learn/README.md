# Kenga Learn

Kenga Learn is derived from the modern Chisimba Reborn reference skin.


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
