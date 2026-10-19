# Installer deprecation follow-up — 17 September 2026

The desktop Gradebook/Kanban update exposed undeclared state in dbTableManager,
modulesadmin, patch and toolbar register. The patch service additionally used both
objModfile and objModFile; PHP property names are case-sensitive. These paths were
not covered by the public-route zero-warning checkpoint of 14 September.

Declare the existing state explicitly, preserve dynamic properties' public access,
and use one protected objModFile property consistently in patch. No blanket
AllowDynamicProperties attribute, diagnostic suppression or vendor edits added.

Verified on the installed local PHP 8.5 runtime:
- tests/maintenance/installer-deprecations.php with CHISIMBA_RUNTIME_ROOT set to
  the installed root: service initialisation and update discovery pass with a
  throwing error handler replacing the legacy handler. This test is read-only.
- Module Catalogue update_dependencies_test.php passes, including hook failure handling.
- Actual idempotent Gradebook 1.473 and Kanban 0.120 installer updates rerun without
  warnings; Gradebook short_name remains present.

This closes the observed installer warnings, not every untested application path.
No production deployment performed.
