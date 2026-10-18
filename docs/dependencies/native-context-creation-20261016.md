# Native context creation controls — 16 October 2026

ContextAdmin 1.432 removes the inline jQuery code-availability checker and optional CPD controls from creation Step 1. The checker debounces requests, ignores stale responses, normalises codes consistently, and distinguishes failed checks from availability. Status text uses registered language items and an accessible live region. The read-only endpoint validates malformed input, retains its creation-authorisation boundary and returns non-cacheable plain text.

Availability is advisory: the creation service still validates submission. No reservation or concurrent uniqueness guarantee is introduced. CPD visibility, required fields, scheme/category filtering and format/navigation defaults use native events; CPD business policy is unchanged.

## Validation

- Native client tests passed for empty/reserved/invalid codes, normalisation, debounce, out-of-order replies, unexpected replies, network failure and retry.
- CPD client tests passed for visibility, required fields, category retention/filtering and navigation defaults.
- Disposable HTTP/PDO checks passed for duplicate/new/malformed codes and anonymous/member denial.
- Chrome on the isolated clone, with global jQuery suppressed: blank/reserved/duplicate/new code states, CPD controls, and successful unpublished course creation through to Step 2. Fresh final tab had no console warnings or errors.
- PHP lint, admission/browser-flow, format-language, settings-discoverability and admin-entrypoint contracts passed. The discoverability test had stale hard-coded English expectations; corrected them to the existing registered configurable terminology.
- PHP diagnostics exposed a dynamic block_updates::$blockType property during member checks; declared the property at its source.

Local ContextAdmin catalogue updated through the normal registration path. Production unchanged. Global jQuery remains enabled outside the disposable rehearsal; this completes one caller, not the full migration. Disposable database, containers and relay removed after testing.
