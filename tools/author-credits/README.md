# Editorial author credits

SimpleBlog's stored account owner remains authoritative for permissions. The
existing `author_credit` provides display attribution; optional `author_kind`
(`user` or `speaker`) and `author_id` select a biography identity separately.
Blank credit restores the owning account name. Published credits are escaped.

The editor provides an authenticated, publishing-policy-guarded Ajax lookup.
Name suggestions return bounded logical IDs and display names, never contact or
credential fields. Users come from the canonical Security user service. Webinar
speakers are queried only when Webinar is registered, and must be published.
Users without a public biography can still be credited; their name stays plain
until a public biography is available. Missing/inactive users, removed Webinar
installations and unpublished speaker records degrade to plain text.

Typing clears a selected identity; selecting a suggestion retains its identity
explicitly, so duplicate names never silently choose a biography. Failed requests
retain typed work. Escape dismisses suggestions, buttons work with the keyboard,
and saves retain the normal CSRF, ownership, transaction and revision checks.
The no-JavaScript editor supports plain credits and explicitly removing a link.

UserDetails exposes a GET/HEAD-only public biography at
`index.php?module=userdetails&action=biography&userid=LOGICAL_ID`. Without `userid`,
Biography remains the authenticated owner's existing editor. The public view
returns only the active user's name, authored biography, portrait and permitted
biography links. Unknown/empty/inactive biographies return 404; mutations return
405. Private profile details and editing controls are not exposed.

SimpleBlog 0.084 adds two nullable columns through the repeatable catalogue hook.
It does not backfill or guess author identities. Imported author names and content
remain unchanged. Reviewed repeat imports refuse to discard a selected identity.
UserDetails 2.014 registers the new unavailable-biography message.

Validation:

- Modules: `author_credit_test.php`, publishing/import/Help/archive contracts.
- Framework: biography and native profile-action/details contracts.
- Disposable PDO: `author_credit_contract_test.php` verifies additive/repeated
  migration, rollback, unchanged ownership and bounded name-only queries.
- Local browser: typed/unmatched names, speaker/user suggestions, saved and
  reopened credit, linked speaker biography and linked public user biography.
- Anonymous local HTTP: public bio 200, missing bio 404, POST 405, author lookup
  denied. Synthetic draft, speaker, path and temporary biography removed.
