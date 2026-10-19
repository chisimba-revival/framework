# Guarded course storage

Implementation branch: `feature/scoped-file-storage`. This is a coordinated
application, web-server and filesystem change. Deploying PHP alone does not close
static-file access. The tested installation is the local PHP 8.5 environment;
no production site has been changed.

## Access and compatibility

Course originals, thumbnails and converted previews are served after the current
record-based permission check. Published public course materials remain public;
private materials require course membership, instructor or administrator access.
Explicit hidden/private restrictions remain in force. Assignment submissions and
intake paths are excluded from generic delivery; Assignment checks submission
ownership and the requested attachment identity.

Public publishing originals retain their existing paths. Personal/public assets
are not moved into course storage. Course visibility cannot be widened merely by
embedding its URL in a blog. To publish the same image independently, deliberately
upload a separate public copy with the existing File Manager/publishing tools and
use that copy's URL. This release does not add an automatic public-copy button or
silently rewrite publishing content. The inventory must identify existing public
references before deployment; resolve any such references explicitly.

The bytes live under private storage. Filesystem compatibility links preserve
legacy readers, uploaders and deleters. The `context` root link also sends future
course uploads to private storage. Thumbnail/force-max root links do the same for
new derivatives. **The links are not an access control:** Apache's guarded routing
must protect every public alias to these paths. The supplied local rule disables
indexes, routes course and derivative requests, and denies direct assignment,
temporary-upload and migration-parking paths. The development image now installs
this rule on rebuild. Production aliases/CDNs need site-specific configuration.

Delivery uses confined real paths, no static redirect, no-store/private caching,
HEAD and bounded single byte ranges. Unknown file/derivative paths fail closed.
Active document types are attachments. Conditional requests do not bypass access
checks or return a cached authorisation result. Previously downloaded/cached bytes
cannot be recalled; purge any deployment caches as part of the site rollout.

## Migration procedure

1. Run `php inventory.php /path/to/ch`. It reports aggregate storage and conservative
   publishing-reference counts, including drafts. It does not change files. The
   scan covers SimpleBlog, content blocks and course pages; inventory additional
   modules, custom aliases, unregistered files and production content separately.
2. Install and verify the guarded routing for the site's actual URL prefix and
   storage directory before touching any files. Check both proxy and backend URLs.
3. Pause writes. Run `migrate-course.py --public-root PATH --secure-root PATH
   --course CODE` for a dry-run manifest; `--apply --writes-paused
   --routing-verified` performs the verified copy and compatibility switch.
   The flags acknowledge operational prerequisites; they do not pause the server.
   Run with permissions sufficient to preserve original ownership/modes.
4. Migrate every child course directory, including empty ones. Then run
   `finalise-context-root.py` with the same roots and apply/prerequisite flags.
   It refuses to switch the root until every child is a verified migration link.
5. Use `migrate-course.py --derivative-directory filemanager_thumbnails` and
   `--derivative-directory filemanager_forcemax` for the generated-file roots.
   For container mount mappings, `--link-root` specifies the secure path visible
   inside the application; omit it when host and application paths are identical.
6. Verify hashes, old links, new uploads/deletion, public publishing and the real
   account matrix. Rebuild/restart the environment and repeat access checks.

Conflicting secure files and unsupported symlinks stop migration without replacing
source content. Copying is exclusive, verified by SHA-256, and checked against a
fresh source snapshot before cutover. Private `.course-migrations` journals retain
originals and manifests. Repeat execution recognises migrated paths. Do not delete
recovery copies until the site's retention/review decision has been made.

Recovery is an operator procedure with writes paused: use the journal to compare
originals with current secure content before restoring anything. Keep routing
active throughout recovery; never restore unrestricted static access or overwrite
new uploads with an old backup. No automatic rollback republishes private bytes.

## Verified locally — 14 September 2026

- 37 existing course originals moved: 37 secure originals, zero public physical
  copies, zero missing files or duplicate originals. Six personal originals remain
  in public storage. Compatibility paths and private recovery copies are retained.
- 74 existing-file HTTP SHA-256 comparisons passed (old and ID URLs).
- 234 real-account request outcomes matched expectations across public/private
  course transitions and revoked membership. Of these, 180 check download bytes;
  54 file-information requests check non-disclosure, not visual preview layout.
- 168 thumbnail/converted-preview role checks passed, plus unknown-ID denial.
- Nine HTTP range/HEAD/cache/method/query-override checks passed.
- Real multipart course and public personal uploads/downloads passed; private course
  upload was denied anonymously. Normal deletion removed both test file records.
- Focused policy, secure download, range, derivative mapping and migration tests
  passed, including conflicts, repeat runs, ownership/modes and future writes.
- Public home, blog, RSS and login journeys return 200. The final rebuilt-image
  capture has zero PHP warnings. Rebuild verification caught and fixed the missing
  local PHP session directory in the image.
- Disposable courses/files/previews removed; sessions logged out; accounts inactive
  with all temporary roles removed. Production data was not used or changed.

Local reference scan: no course-file matches in two SimpleBlog records or five site
blocks; matches in 18 of 141 course pages. All 37 original links were subsequently
verified by hash. This does not certify production content or custom modules.

Detailed HTTP results and migration manifests remain in the workspace under
`work/php-warning-audit/course-access`. Reproducible focused tests are in
`tests/fileaccess`. Keep this branch separate until the target site's inventory,
server configuration and rollout checks have been reviewed.
