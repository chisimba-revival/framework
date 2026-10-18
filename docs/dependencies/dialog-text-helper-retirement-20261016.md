# Dialog/text helper retirement — 16 October 2026

Removed jquery jqdialogue, jqexpanding and jqtruncate, jquerycore dialog, the
jGrow asset and both bundled truncate packages/examples: 13 files listed in
../../tests/dependencies/retired-dialog-text-helper-paths.json.

Cross-repository name/caller and direct asset/plugin-call searches found no
non-paused runtime consumers. Remaining truncate calls were bundled examples.
The jquerycore dialog's remaining callers are preserved in paused modules and
must be migrated before revival. CMS Boxy, jqGrid and tabs remain, as do global
jQuery 1.7.2 and UI 1.8.18. No global loading or library upgrade was attempted.

Read-only selected text/relevant varchar column scans on local and six sites
found zero matching references for the helper names and resource paths. Receipts:
work/dialog-helper-trace-20261016 in the parent workspace. This does not cover
arbitrary aliases, encodings or external integrations.

Passed: retired-asset/CMS-boundary test, actual jquerycore loader/stale-session
regression and native-profile action tests. Chrome local My Profile renders;
compact/full Help and Escape focus return work; console warnings/errors empty.
No profile fields were changed. Module versions: jquery 0.065, jquerycore 0.019,
htmlelements 0.623. No schema changes or production deployment.

CMS feasibility and suggested project sequence are documented separately in
cms-revival-assessment-20261016.md. Its dependencies remain intentionally retained.
