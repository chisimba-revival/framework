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

## Completion status

Both rehearsals passed: catalogue updates ran twice and native runtime checks
passed under strict warning handling. Business-data fingerprints are unchanged
across 107 tables on chisimba.com and 171 on KengaLearn. Deployment is pending;
the final verification section must record completed release paths.

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
