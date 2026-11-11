# Readable content addresses

Pages, site SimpleBlog posts and webinars use one Short URL namespace. A blank
address generates a lowercase, hyphenated title on save. Duplicate automatic
names get a numeric suffix; explicit collisions are rejected. Title changes keep
the address. Renaming retains the old path. IDs, old query links, QR redirects,
publication state and access policies remain authoritative. Personal/course posts
and speaker biographies are outside this rollout.

## Coordinated rollout

Deploy the common framework and modules together using the source-manifest
workflow. Update Short URL, SimpleBlog, Pages and Webinars (where installed)
through Module Catalogue, preserving data. Short URL 1.014 adds the registered
`tbl_shorturl_content` table. Do not create it during requests. Repeating the
catalogue update is supported. Back up the database and server configuration.

Before enabling `shorturl.content_paths_enabled=TRUE`, configure and test the
server route. Audit exact WordPress redirects, existing QR mappings, static
files/directories and other root endpoints. Add their single-segment names to
`shorturl.content_paths_reserved` (comma separated). Existing Short URL database
mappings and Pages names are reserved automatically. Keep existing exact routes
and `/go/` routing ahead of the generic content rule. Never replace the site's
configuration wholesale with this example.

Apache directory-context example (works in the app root or a `/ch/` directory):

```apache
RewriteEngine On
# Existing exact migration and /go/ rules stay above this.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*/?$ index.php?module=shorturl&action=content [L]
```

Do not add `QSA`: user parameters must not replace the fixed owner route. The
engine resolves the original request path, accepts GET/HEAD only and dispatches
to the normal owner's view. An unknown path returns 404. With Nginx, the same
single-segment path must internally reach the front controller with only
`module=shorturl&action=content`; preserve the original `REQUEST_URI` and existing
FastCGI security/static rules. Choose the rule in the context of the actual
proxy/app configuration, not by copying an unrelated server block.

Enable the flag only after the route works. New saves then allocate addresses.
Backfill published existing content without resaving or changing publication
chronology:

```sh
php tools/content-paths/backfill.php /absolute/installed/app
php tools/content-paths/backfill.php /absolute/installed/app --apply
```

The first command performs the real allocation inside a rolled-back transaction
and reports the proposed names. Review before applying. Apply is atomic and
repeatable; already assigned content is skipped. Drafts receive a path on their
next save, but remain private. An imported page name occupied by a QR route gets
a free canonical address; its old query-by-slug name is retained as an alias.
No legacy redirect is edited. If the namespace audit finds ambiguity, resolve
that configuration explicitly before rollout.

Verify public and anonymous URLs, old ID/QR links, draft/trashed denial, query
parameter tampering, HEAD, POST rejection, sharing/feed links, and saving a
renamed address. For webinars, also check the booking form and return URL; the
form continues posting to the existing CSRF-protected registration endpoint.
Disabling the flag restores generated ID links but makes new readable paths
unavailable; retain the route and registry after a successful rollout.

## Checks

```sh
php ../modules/shorturl/tests/contentpaths_test.php
CHISIMBA_MODULES_ROOT=../modules python3 tests/database/run-pdo-contracts.py chisimba-php85-web contentpaths_contract_test.php
```

The latter uses disposable Docker/MariaDB fixtures, not the installed site.

## Validation on 11 November 2026

The focused PDO fixture, page/publishing/editor policy checks, webinar scheduling
and newsletter checks passed. Local HTTP checks verified generated/manual/old-ID
routes, ignored override queries, GET/HEAD and POST rejection, unpublished and
archived pages/webinars, booking form routing, and repeatable dry-run/backfill.
Browser checks verified blog and webinar save/rename/collision flows, Help and
390px layout. No mail was sent; no production content was changed.

The unrestricted PDO suite currently stops in the existing
`NavigationRegistrationFixture` (missing `getObject` after toolbar changes).
The focused content-path fixture runs independently and passes. This is not a
claim that the entire repository test suite passes.
