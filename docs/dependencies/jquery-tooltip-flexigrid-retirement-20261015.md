# Retire unused jquerycore plugins — 15 October 2026

Following the [source and six-site stored-reference check](jquery-plugin-production-check-20261015.md),
jquerycore 0.012 removes its unused tooltip and Flexigrid wrappers, their XML
catalogue entries and bundled resources. The tooltip-only bgiframe, delegate and
dimensions entries/resources are also removed: 29 tracked files, 104,717 bytes.

No loader compatibility layer or user-session reset was needed. The existing
catalogue allowlist ignores retired names, including selections left in old skin
sessions. Core/UI versions, theme selection and suppression remain unchanged.
The six retained plugins are Validate, Form, Corner, LiveQuery, PNG fix and oEmbed.
Older jquery-module/module-bundled plugin copies remain because their callers
need separate migration. Native UI tooltips and the legacy htmlelements helper
were not changed. No application module was enabled or paused by this change.

## Verification

- Added a standalone regression using the actual skin forwarding and core loader,
  real catalogue and asset paths, with isolated service/session doubles. It failed
  against the original catalogue as expected, then passed after retirement.
- Passed stale-only, mixed stale/retained and unknown selections; all six retained
  plugins; duplicate selections and both setter APIs; unchanged session data;
  configured core/UI/theme, suppression and emitted asset existence.
- Passed on host PHP and the installed local PHP 8.5 image, treating PHP warnings
  as failures. PHP syntax and Git whitespace checks passed.
- Updated the local jquerycore registration through normal catalogue installation.
- An installed-engine fixture used a separate CLI session to feed mixed stale,
  Form and Validate selections through the real skin. Session selections remained
  unchanged and only retained plugin URLs were emitted. Its temporary HTML page
  contained no server-side endpoint or real account data.
- Chrome verified that required-field validation rejects empty input and accepts
  valid text, Form serialises the value, and the native UI tooltip appears on
  keyboard focus. Captured console warnings/errors were empty. The real Personal
  Space page and editing toggle also worked after retirement.
- Removed the temporary served HTML fixture after testing. No real user's session,
  account, placement or content was modified by these checks.

Run `php app/core_modules/jquerycore/tests/plugin_retirement_test.php` from the
framework checkout. Local installed/browser-fixture evidence is under
`work/jquery-retirement-20261015` in the parent workspace.

Deployment status is recorded separately; this change requires no schema update.
The next remaining dependency work is migrating the older optional-module caller
clusters, then considering conditional core/UI loading. This is not total jQuery
removal and does not justify deleting the older copies by filename alone.
