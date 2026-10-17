# Workgroup validation: private download response

File Manager 1.114 terminates the response after the authorised legacy private
file download. Previously folderaccess::downloadFile returned after readfile,
allowing the application renderer to append its page to the downloaded bytes.
The existing file-read policy and secure-path checks are unchanged.

Validated with the coordinated modules Workgroup regression fixture on an isolated
PDO clone: owner receives exactly the synthetic file bytes; peer/outsider receive
no private bytes. Public download succeeds. PHP lint and whitespace checks pass.
The Workgroup suite also covers CSRF, ownership, stale edits, savepoint rollback,
idempotent submission and current group/course/teacher membership revocation.
No PHP warnings/deprecations were observed in the exercised clone routes.

The legacy denial template still returns HTTP 200; tests assert denial content and
absence of private bytes, rather than claiming HTTP 403 for that older route.
Local catalogue updated; no production deployment in this batch.
