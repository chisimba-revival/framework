# Secure outcomes and plugin saves — 17 October 2026

Context 2.063 and ContextAdmin 1.434 secure the final course-wizard steps. This is a security checkpoint in the continuing jQuery cleanup; it does not disable global jQuery or certify unrelated module endpoints.

## Behaviour

- Current persisted administrator or course-author authority is required for rendering and saving. Writes require POST, exact session/submitted course scope and a token separated by both course and step. Authority is checked again inside the locked save.
- A dbTable-backed repository locks the course row, compares a snapshot revision and applies the complete change in a checked savepoint transaction. It refuses non-transactional tables. Existing outcome identities are preserved; submitted foreign IDs and unoffered plugins are rejected.
- Explicit outcome removal is blocked if the outcome has declared dependencies or Assignment links, including when Assignment is unregistered but its table remains. New and edited rich text passes through the shared sanitiser.
- Plugin updates change offered selections only, preserving saved selections unavailable in this form. This does not install or enable a module site-wide.
- The native form preserves rich-text drafts on expiry and failures. Adding a field does not save. Back explicitly leaves without saving. Stale or unconfirmed saves disable submission and offer the current version in another tab for reconciliation. An interrupted response is not assumed to mean no write occurred.
- Shared Help explains saving, linkage, expiry and conflicting edits. The old Step 3/4 templates and duplicated mutation branches are removed.

## Read-only outcomes and deployment requirement

`getContextOutcomes()` now performs a bound SELECT only. The previous reader could insert a legacy goal, clear that goal and change comment/alert settings during a view. Legacy conversion is now an explicit Context catalogue post-install hook. It locks rows, copies each legacy goal once, and changes only the legacy goals field. It is repeatable and uses savepoints; migration attribution uses the original owner when compatible with the legacy audit column, otherwise a system marker.

**Deploy source together with the normal Context and ContextAdmin catalogue updates.** Do not rely on a page view to migrate legacy data. Until an installation runs the update, legacy goals have not been converted into selectable outcomes. Existing outcomes and IDs remain intact. No schema changes are required.

## Validation

Passed syntax/whitespace checks and existing information-save, settings-authority, tiered-admission and admin-entrypoint tests. New policy tests cover method/scope/current authority, purpose-separated tokens, rejected requests, no-write field addition and recovery.

The disposable PHP 8.5/PDO suite exercises reader non-mutation, repeated migration, preserved comment/alert settings and IDs, valid edits/additions, foreign IDs, linked-outcome rejection, stale outcomes/plugins, forged plugin rejection, preservation of unoffered selections, injected failures after outcome updates and plugin deletions, and nested/outer rollback ownership. Fixtures are rollback-only and require the explicitly guarded clone.

Installed HTTP tests pass for expired-token draft renewal without writes, successful outcomes saves, stale rejection, plugin allowlisting and idempotent saves, anonymous/member denial, current instructor access without administrator membership, and denial after both administrator and instructor membership are revoked. The dual role matters: losing administrator membership alone must not revoke a legitimate instructor's course access.

Chrome, with global jQuery suppressed, confirms adding an outcome retains typed rich text; Help opens and Escape closes it; one tab can save while the older tab preserves its draft and disables saving; plugin selection persists and displays confirmation. No application console errors were captured. Grammarly extension warnings were separate from application diagnostics. Final installed runs have no new PHP warnings/deprecations/fatal errors.

Real local catalogue updated through the canonical path: zero legacy goals required conversion, and comparison confirmed all unrelated course fields unchanged. Production unchanged. Disposable test environment removed after checks. User-owned paused-module moves remain untouched.

## Next

Resume tracing shared jQuery callers, including notification helpers and legacy File Manager selection/pagination. CMS remains deferred. This checkpoint closes the identified final-wizard write boundaries; it is not a claim that all legacy security or jQuery work is finished.
