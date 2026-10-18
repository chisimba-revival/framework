# Shared jQuery remnants — 17 October 2026

Removed the unused Context utilities `searchBlock_()` and `searchBlock__()`
branches, which loaded jQuery 1.3.1/1.2.3, autocomplete and tablesorter.
The routed `searchBlock()` method remains unchanged.
Removed File Manager's unused `multifileselect` and `multifileselect2` classes.
The maintained `selectfile` picker and native File Manager bulk selection remain.

Source tracing across framework and modules, including paused modules, found no
callers of the removed methods/classes. A read-only local stored-content scan of
195 candidate columns found no references. This is not a production content scan.
No database tables, rows, uploads or permissions were removed.

Installed PHP 8.5 rendering with warnings converted to exceptions exposed existing
dynamic properties in Context utilities, dbcontextparams and selectfile. Declared
those existing service properties; no suppression or behaviour changes.

Local catalogue updates to Context 2.065 and File Manager 1.115 passed twice and
retained Context search/native selector rendering then passed with warnings as
errors. PHP syntax, whitespace, 50 file-access policy assertions, revoked-owner
hidden-file protection, 22 download/delivery assertions and 8 derivative mappings
passed. Chrome checked the local home page and File Manager, including select-all
and clear counts (2 then 0); no captured warning/error console messages. No files
were changed by browser testing. Global jQuery remained enabled during this smoke
check; this does not certify a globally jQuery-free installation.

## Remaining work

Textblock and Slate scripts are **live**, not orphan assets: disclosure and deletion
controls still use them. Replace those clients with native behaviour and check
POST/CSRF/revision/error handling end to end. Wall, SimpleGal and the ePortfolio
upload workflow also remain live source callers. CMS/CMS Admin revival is deferred.
Old content filters, Blogcomments and other smaller assets still need reachability
and stored-use checks. The separate old AJAX `[BLOG:]` filter had no stored matches
locally; production content has not been checked in this round.

Global loading stays enabled until those consumers are migrated or have an explicit,
reviewed module-scoped compatibility path. No production deployment this round.
