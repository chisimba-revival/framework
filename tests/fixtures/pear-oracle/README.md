# Historical PEAR comparison fixtures

These unchanged, licensed sources were moved out of `app/lib/pear` on
12 October 2026 after the native replacements passed their contract tests and a
full application/module source audit found no remaining imports. They are test
oracles, not runtime dependencies. Never include `tests/` in a web release or
add this directory to the application include path. `bootstrap.php` is CLI-only.
Original copyright/licence notices remain in each source file.

Packages: Config, Event Dispatcher, Translation2, I18Nv2, HTML TagCloud and
HTML BBCodeParser. Runtime compatibility dependencies needed by some comparison
tests still resolve from app/lib/pear (notably PEAR, XML and DB adapters).

Recover historical application locations from the parent commit if an external
extension unexpectedly imports these packages; migrate that extension before
releasing. Do not silently reinstall them through an assembly script.
