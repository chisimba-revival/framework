# PHP 8.5 maintenance checkpoint — 14 September 2026

The observed public-route warning baseline was reduced from 1,975 occurrences to
zero across home, SimpleBlog list, RSS and anonymous editor/login, including a
cold start. No diagnostic suppression was introduced. Changes declare existing
state, correct obsolete syntax/reference handling and preserve legacy contracts.
Toolbar security/context lookup uses the canonical services and has focused
regression coverage. Bundled PEAR/Lucene compatibility patches remain local
maintenance patches to those dependencies, not dependency replacements.

All changed PHP files passed syntax checks at checkpoint. The recorded focused
regression suite and file-access behavioural tests passed again before commit.
Authenticated module journeys still expose additional warnings; this is not a
whole-application clean bill of health. UTF-8/Latin-1 conversion and emoji storage
modernisation are explicitly deferred.

File access tests are in tests/fileaccess; they expect the sibling modules checkout
for Assignment integration. Real-account HTTP results and cleanup are described
in file-access-2026-09-14.md. The direct public-storage URL bypass remains unresolved.
No production deployment was performed as part of this checkpoint.

Detailed working logs remain in the workspace's work/php-warning-audit directory.
