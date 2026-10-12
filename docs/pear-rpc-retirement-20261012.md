# XML-RPC caller cleanup — 12 October 2026

Removed 32 implementation files from core API and Packages, approximately 10,200
lines. Their controllers already reject every action with HTTP 410 and do not
instantiate services. Keep both controllers and retirement templates so historical
URLs continue to receive an explicit response.

Caller tracing across framework and module PHP found one live external core-API
class consumer: Filters' `parse4tracwiki` instantiates `tracrpcclient`. That class
is retained. The SimpleGal reference to core `bloggerapi` is a comment, not a call.
The remaining removed classes only served the retired API implementation or had
no application caller. The deletion list is in
`tests/dependencies/retired-rpc-paths.json`; apply it during deployment. Source-copy
updates alone do not remove obsolete files from an assembled release.

Validation: executable endpoint tests reject all legacy actions without loading
services; absence guards and Active Dynamic Mirroring retirement tests pass.
Local actual API and Packages HTTP responses both remain 410 with the retirement
message and no PHP diagnostics in response bodies. Browser confirms the API
response. Native local PDO, translation, country resources, catalogue and MFA
composition smoke passed. No database schema or content changes.

A read-only registration audit on all six production sites (chisimba.com,
KengaLearn, Learn the Birds, KengaPub, KengaSolutions and Heritage Treazures) found
CMS, CMSAdmin, SimpleGal and IM unregistered; API and Packages remain registered
as retirement endpoints. This is registration evidence, not an access-log or
historical-content usage audit. Production source is unchanged by this batch.

## Remaining XML-RPC consumers

- CMS remote publishing (`cmsrpcapi`, `rpcdbcmsadmin`).
- SimpleGal remote publishing (`simplegalxmlrpcapi`, `simplegalmetaweblogapi`).
- Historical standalone `modules/im/client.php` demonstration of the retired API.
- Filters for remote BLOGPOST, SCREENSHOT and TRACWIKI content. The last uses the
  retained core API Trac client. These can be invoked by legacy stored content;
  don't silently remove their output or assume the entire Filters module unused.

Derek subsequently authorised retirement of CMS/SimpleGal publishing. Their
controllers now return HTTP 410 before initialisation for the retired actions;
four publishing implementation classes are removed in the modules repository.
GET/POST, mixed-case and normal gallery dispatch tests pass on PHP 8.5. The
legacy CMSAdmin checklist no longer loads the removed RPC repository.
PEAR XML_RPC itself is retained for the content filters. Retiring optional MDB2 and its XML
schema dependency chain remains separate work. All six production backends
already run PDO, but framework compatibility code and tests still support MDB2.

Local diagnostics and the registration-only site inventory are in workspace
`work/pear-rpc-20261012`. No private content, credentials or email were extracted.
