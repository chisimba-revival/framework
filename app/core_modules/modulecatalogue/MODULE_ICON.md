# Module catalogue icon contract

Every maintained module declares one semantic Lucide icon in `register.conf`:

    MODULE_ICON: book-open

The module owns the semantic choice. The `ui` module owns the allowlisted SVG
assets and safe renderer. The skin owns size, colour, spacing and background.
Catalogue page loads are read-only. The explicit **Update catalogue** action
rebuilds catalogue metadata and removes database registrations for directories
that no longer exist; it never uninstalls modules or deletes module-owned data.

Unknown or invalid icon names render as `puzzle`. This is only a safety fallback;
new and maintained modules must choose an intentional allowlisted icon.
