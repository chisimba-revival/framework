# Quota administration checks

Run `php quota_query_test.php` and `php quota_save_test.php` from this directory,
and the Navigation `pagination_client_test.js` with Node. These exercise actual
query construction and controller recovery with failure-injectable boundaries.

Before release, use a disposable installed PDO clone with outbound workers disabled.
Create synthetic users through userprovisioningservice and quotas through dbTable.
Check searches, two-page results, whole-number/default saves, hostile query fields,
CSRF expiry/retry, unchanged data after rejected requests, stale concurrent saves,
and anonymous/member/revoked-administrator access. Test the real browser without
jQuery, including Help, keyboard operation and failed page request recovery. Never
create test users, revoke permissions or change quotas on a production site.

The dated evidence and limits are in
`docs/dependencies/native-quota-pagination-20261017.md` at the framework root.
