# Historical PEAR comparison fixtures

These unchanged, licensed sources were moved out of `app/lib/pear` on
12 October 2026 after the native replacements passed their contract tests and a
full application/module source audit found no remaining imports. They are test
oracles, not runtime dependencies. Never include `tests/` in a web release or
add this directory to the application include path. `bootstrap.php` is CLI-only.
Original copyright/licence notices remain in each source file.

Packages: Config, Event Dispatcher, Translation2, I18Nv2, HTML TagCloud and
HTML BBCodeParser. PEAR core, XML Parser and XML Util are also retained here solely for historical
comparison tests. The application PEAR directory is removed. No runtime imports
resolve from this fixture directory. MDB2 is not retained; its recorded result
and schema baselines cover the native database contracts.

Recover historical application locations from the parent commit if an external
extension unexpectedly imports these packages; migrate that extension before
releasing. Do not silently reinstall them through an assembly script.
