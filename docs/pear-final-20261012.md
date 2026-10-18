# Final PEAR runtime retirement — 12 October 2026

PDO MySQL is now the sole supported database backend. The deployable `app/lib/pear`
directory is removed, including MDB2, MDB2 Schema and their XML helpers. The
engine, dbTable and schema manager use the existing native implementation.
Explicit MDB2 configuration fails with an actionable error; missing backend
configuration defaults to PDO without rewriting the configuration file.

The installer neither stores nor reads a PEAR location. Missing pdo_mysql blocks
installation. Legacy PEAR resource loading and XML tree import fail explicitly.
Unused OpenID SQL adapters have no application callers and are removed from both
bundled copies. HTMLSax's two invalid-argument paths now throw native exceptions
(a local vendor modification). Membership mutation checks use dbTable's false
failure contract. The unused private MDB2 bulk helper is removed.

Historical licensed PEAR comparison fixtures remain under tests only, including
the minimal core/XML dependencies used by configuration comparisons. They are
not deployed and are not a runtime dependency. Dormant/paused modules are not
certified by this removal: audit and modernise their dependencies before enabling
them. Do not restore a PEAR runtime to awaken an old module.

## Validation

- PHP lint and whitespace checks on changed source.
- Disposable PDO suite: recorded CRUD/portability behaviour, prepared statements,
  schema/indexes, catalogue repeat updates, module repositories, permissions,
  authentication races, transaction ownership/rollback and disconnected commit.
- Schema columns compared with a fixed historical MDB2 baseline captured on
  MariaDB 10.11.18 before removal. No MDB2 import remains in the PDO tests.
- Native installer created all 107 registered definitions and repeated seed loads.
  Requirements tested with and without PDO MySQL, including forged bypass input.
- Translation storage: Unicode, exact whitespace/empty values, language lifecycle,
  cache invalidation, rollback and two concurrent PDO writers.
- Configuration/translation/events/parser/error/retirement and membership tests.
  Historical comparisons load only CLI test fixtures, never application libraries.
- Local assembled PEAR directory physically removed into a recoverable container
  backup. Installed authentication, configuration/events/catalogue, translation
  and classification smoke checks pass. Root-owned configuration write refusal passes.
- Browser: authenticated administration, catalogue and user administration load
  with PEAR absent. No new browser PHP warnings or deprecations observed.

This is not a claim that every dormant module is PHP-8-ready. Existing logger
warnings earlier in the local log predate these checks. The full fresh-install
browser wizard was accepted on 11 October; this final pass repeats its automated
requirements, configuration and real schema gates, not that entire wizard.

## Release boundary

The final release is now deployed; the verified results follow.

## Completed production rollout

All six sites were deployed and verified on 12 October 2026, beginning with
Chisimba.com. Each now runs without an application `lib/pear` directory.
The deployed framework source is `e082c178e`, modules `02498125c`; release tooling
and the empty dependency pack were based on dev-environment `9179440`.

| Site | Release stamp (UTC) | Protected tables checked | Result |
|---|---|---:|---|
| Chisimba.com | 20261012-123301 | 107 | Online, verified |
| KengaLearn | 20261012-123421 | 171 | Online, verified |
| Learn the Birds | 20261012-123535 | 109 | Online, verified |
| KengaPub | 20261012-123740 | 222 | Online, verified |
| KengaSolutions | 20261012-123838 | 218 | Online, verified |
| Heritage Treazures | 20261012-123936 | 218 | Online, verified |

All final manifests matched, PEAR paths were absent, and the checked post-release
PHP log deltas contained zero new diagnostics. Mail timers were active on all four
sites with configured mail timers. Protected business rows and editable site
navigation remained unchanged across each switch. Previous releases and verified
database/persistent-file backups remain available on each server.

Browser checks covered every site: sign-in pages/session status, KengaLearn's
authenticated Kanban boards, and Learn the Birds' webinar management and reminder
dashboard. No test email was sent and no real board or webinar was edited. The
LTB dashboard retained its reminder schedule and reported a resumed worker run.

Historical test oracles, dormant-module references and recoverable old releases
are intentionally distinct from the deployed runtime. Retiring those records is
not required for PEAR-free operation; dormant modules still require modernisation
and testing before activation.
