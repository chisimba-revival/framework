# Staged PDO rollout — 11 October 2026

Authorised order: chisimba.com, then KengaLearn. LearnTheBirds must wait for
Derek's notice; the other three sites remain unchanged until a later decision.
This is a PDO migration milestone, not final PEAR removal.

## Source and preparation

- Framework `097dd76b2`, modules `1485314d6`, dev-environment `e17231b` and
  chisimba-info `5ddba8f` were committed and pushed to their existing branches.
- Both production images used PHP 8.5.4 without `pdo_mysql`. Each candidate is
  derived from that site's exact running image and adds the MySQL PDO extension.
  No shared image tag used by another site is replaced.
- Source hashes were compared for 25,434 source files per site. Existing files
  that differ from the candidate match earlier ancestors in Git; no conflicting
  site-only changes were found in this set. A shared 668-file overlay includes
  missing predecessor fixes, required native services and the PDO migration.
  The assembled PEAR library and site configuration are preserved.
- Each site has its own copied candidate and restored database in an internal
  Docker network, without public ports or access to live uploaded files. Strict
  PHP warnings are failures. Tests do not send email, payments or AI requests.
- Both exact production images passed the native PDO suite: binding and legacy
  value parity, ACL/classification, module repositories, transactional ownership,
  concurrency, failures, shared schema/catalogue and 107 installer definitions.
- Rehearsal exposed undeclared properties in the older system-terminology
  installation hook; the shared source now declares them explicitly.
- The normal Canvas dependency on User Configuration is absent on chisimba.com;
  its installation is included in that site's catalogue rehearsal. Unregistered
  application modules are not generally enabled by this release.
- Catalogue updates regenerate `tbl_module_blocks` row IDs. Their values are
  checked against the committed block declarations; they are catalogue metadata,
  not user-authored block content. Business-data checksums remain a separate gate.

## Completed deployment and live verification

Both sites are online using `DATABASE_ABSTRACTION=PDO`, in the authorised order.
Runtime source: framework `2986e585b` (including the extra terminology-hook fix)
and modules `1485314d6`. The shared overlay manifest SHA-256 is
`78a07c9eee826daf4b5b6a6863ab56589bbb062fc21ac899cc1c99c5e8e6c6d6`.

| Site | Release directory | Backup directory | Protected tables |
| --- | --- | --- | --- |
| chisimba.com | `/srv/chisimba-com/releases/release-pdo-20261011-125528/ch` | `/srv/chisimba-com/backups/pdo-20261011-125528` | 107 |
| KengaLearn | `/srv/kengalearn/releases/release-pdo-20261011-125725/ch` | `/srv/kengalearn/backups/pdo-20261011-125725` | 171 |

- Database dumps and configuration/uploads/session archives were checked before
  switching. Catalogue updates and strict native runtime verification ran while
  web writers were stopped. Protected table fingerprints matched before/after.
- Chisimba.com has no registered System Management module. Its window used a
  stopped web container; an initial unset-flag guard stopped before any live
  change. No maintenance module was installed just for the deployment.
- KengaLearn's existing maintenance flag was enabled, its two AI/worksheet worker
  locks were held, and its web container was stopped. Maintenance was cleared
  before the verified candidate reopened. No worker was triggered by the tests.
- Live browser checks on chisimba.com: homepage, published blog post, registration
  and password-recovery forms, without submitting either form.
- Live KengaLearn browser checks: preserved administrator session, home and site
  administration, permissions, question banks, Kanban, Question Generator,
  knowledge maps, products, notes, catalogue, pages, maintenance, existing course
  home/chapter listing and chapter editor. No content changes were saved; the
  session was restored to Lobby. No payment, mail or AI request was submitted.
- Final checks inside each actual running web container confirmed native PDO,
  configuration/translation/authentication/MFA/catalogue composition, healthy
  container status and exact deployed file hashes. Disposable rehearsal containers
  and networks were removed.
- Both browser consoles were clean at the final checks. Across the complete
  recorded deployment/browser windows, the checked PHP log files contained zero
  new warning/error lines (one file on chisimba.com; three on KengaLearn).
- The non-target containers on the shared chisimba.com host retained their exact
  container IDs and image references: LearnTheBirds, KengaPub, KengaSolutions,
  HeritageTreazures and their databases were not recreated or updated.
- Production browser checks use public routes on chisimba.com and an existing
  authenticated administrator session on KengaLearn. They do not constitute a
  fresh password/MFA browser challenge or destructive production workflow tests.
  Those boundaries have separate isolated service/concurrency/local evidence.

The dated deployment operators and manifest are saved in the dev-environment
repository under `tools/pdo-rollout-20261011/`. Private databases, installation keys,
backups and rehearsal outputs remain outside Git. This record describes tested
workflows, not a guarantee that every dormant module is PHP 8/PDO compatible.

## Rollback and remaining scope

Retain the previous release, exact image ID, original Compose configuration,
original database abstraction setting, consistent database dump, and persistent
configuration/uploads/session archive. Coordinate code, image and configuration
when reverting. Never automatically restore a live database over newer writes;
inspect any catalogue changes before choosing the recovery path.

Other sites continue to use their existing code, images and database settings.
PEAR error handling and other consumers remain as listed in the dependency
register. Dormant modules and legacy import/operator scripts require their own
migration and PHP 8 checks before use. Fresh password/MFA browser acceptance is
separate from authenticated-session and installed authentication-service checks.
