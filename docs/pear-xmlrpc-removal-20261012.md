# PEAR XML-RPC removal — 12 October 2026

Removed XML/RPC.php, XML/RPC/, and the final core Trac RPC client. The dependency
manifest, builder and curated pack are updated so rebuilding does not restore it.
The historical standalone IM demonstration client now returns 410 without making
requests. Paused Blog has a reactivation warning; SimpleBlog is unaffected.

Filters 1.565 retains parse4blogpost, parse4screenshot and parse4tracwiki as
no-op parsers: they return input unchanged and initialise no services. Imported
legacy markup remains visible/editable rather than silently disappearing or
causing remote calls. Existing cache files and stored content are not deleted.
If these features are needed later, rebuild them using maintained shared services.

## Usage evidence

A read-only dbTable scan checked all char/varchar/text-family columns in the
current database of every managed site for BLOGPOST, SCREENSHOT
and TRACWIKI tag prefixes. Only per-table counts were retrieved; no content or
credentials were printed. There were no matches:

| Site | Tables with text columns checked |
| --- | ---: |
| chisimba.com | 134 |
| KengaLearn | 198 |
| Learn the Birds | 136 |
| KengaPub | 249 |
| KengaSolutions | 245 |
| Heritage Treazures | 245 |

This does not scan uploaded files, backups, external content or binary columns.
That limitation is why the parsers preserve all input instead of rewriting data.

## Validation and deployment scope

Strict PHP8 tests cover empty/plain/Unicode/legacy/malformed input, service-denying
fixtures, absent retired libraries, and retired API/CMS/SimpleGal routes. Real
local engine composition of all three filters passed without XML-RPC on disk.
Native translation, catalogue and authentication smoke passed. Full PDO and MDB2
disposable-database suites, including 107 native installer schemas, passed.
Installer requirements pass; dependency-builder shell syntax passes. No upstream
packages were redownloaded merely to remove a manifest entry.

Source and local assembled runtime updated. Production has not been changed.
Deploy using the shared retired PEAR/RPC path manifests plus the IM client stub;
copying files alone leaves obsolete classes behind. Local XML-RPC backup is in
container `/tmp/pear-rpc-retired-20261012`. Private audit/count evidence and test
logs are in workspace `work/pear-filters-20261012`.

The remaining removal work is the optional MDB2 backend, its schema/XML helper
chain, and finally PEAR.php after tracing residual error/utility references.
