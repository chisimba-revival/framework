# Native shared dashboard editors — 16 October 2026

My Learning 1.016, My Teaching 0.207 and My Administration 0.110 now supply the
configuration required by the native Context editor (Context 2.060). Previously
they loaded the client without settings, so their editing switches did nothing.
Their legacy request handlers also lacked CSRF and safe placement scope checks.

The shared `dashboardblockeditor` fixes its scope from a controller literal,
checks the current persisted administrator role, and uses the existing
`contextblockeditor` / `dbcontextblocks` / `blockplacementorder` path. Each dashboard
has its own token purpose, revision and layout. Its role-filtered, registered,
available chooser is also the server preview/add allowlist. Contentblocks remain
outside these dashboard choosers, as before. Personal dashboard audiences remain
module-owned. Administrative operational actions retain their owning services and
CSRF purposes, behind the stronger persisted administrator check.

The templates supply shared labels, tokens, a revision captured before rendering
placements, native controls and quick/full contextual Help. Obsolete inline editor
globals and raw add/remove/move handlers are removed. No schema change is required.
Deploy framework and modules together and run normal Module Catalogue updates.

## Validation

- Shared policy tests: all three audiences, unknown/unconfigured scope, anonymous
  and revoked cached administrators, POST/CSRF, foreign scopes, unavailable,
  unregistered and unoffered blocks, and delegation to checked persistence.
- Existing dashboard behavioural/contract suites and Site Home policy tests pass.
- Shared native JavaScript tests pass for each dashboard route: preview races,
  duplicate submission prevention, token renewal, retained selection and uncertain
  write lockout.
- Isolated PHP 8.5/PDO clone: canonical catalogue updates twice; HTTP preview,
  add, reorder, remove, stale revision, GET/CSRF and foreign-placement rejection on
  all three dashboards. Original layouts restored after the completed HTTP tests.
- Anonymous, ordinary member and cached-but-revoked administrator HTTP requests
  cannot edit. Persisted membership restored after testing. Real cross-dashboard
  placement IDs and tokens are rejected. Denials preserve layout revisions.
- Shared PDO ordering test passes, including injected partial-write rollback,
  nested savepoint and outer rollback, and cross-scope denial.
- Chrome with automatic jQuery/UI loading suppressed: all three dashboard editing
  switches and previews work; My Learning add/reload/remove and Escape cancellation
  work. Quick/full Help and Escape work; the closed drawer is aria-hidden and inert.
  No jQuery script is present on the tested dashboard pages. Desktop appearance
  and keyboard controls checked; no new responsive CSS was introduced.
- No browser warning/error entries or PHP warning/deprecation/fatal log matches
  in the disposable test runtime. PHP lint and whitespace checks pass.

The private local evidence directory is `work/no-jquery-rehearsal-20261016` in the
workspace (HTTP scripts and screenshot). Disposable containers, database, relay
and credentials are removed after testing. No production deployment. The normal
core jQuery loader remains enabled: this completes another native workflow, not
all jQuery removal. Context settings and other active loaders still need tracing.
