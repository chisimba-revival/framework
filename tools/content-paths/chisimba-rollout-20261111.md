# Chisimba.com readable-address rollout — 11 November 2026

Deployed to Chisimba.com only, at the owner's request. Learn-the-Birds webinar
acceptance and the remaining sites are deferred until the owner tests this site.

- Framework: `a684081a82fd4abd2299a6a62c0deb1fed03ec3b`
- Modules: `8a18710327a6388655ee65769a16ec334ab07130`
- Common branch: `release/consolidation-20260930`
- Complete source manifest: `cbb790448f3c9722a05fe0f9a6f056d083e55231924867ce00c70a6ab5941452`
- Operator/evidence: `/srv/content-slugs-chisimba-20261111`
- Release: `/srv/chisimba-com/releases/common-dev-20261111-042707/ch`
- Backup: `/srv/chisimba-com/backups/shared-20261111-042707`

The existing source matched its previous manifest before staging. The isolated
clone rehearsed registration twice and verified native PHP/PDO, authentication,
login/reset pages and all generated addresses. The live switch preserved all 130
protected data-table fingerprints and existing navigation. Short URL's three new
catalogue tags were separately verified; only those new installation metadata
rows were excluded from the old-row fingerprint. No post content, publication
dates or ownership changed. No email or worker jobs ran during rehearsal.

Short URL was not previously installed on Chisimba.com. Its normal catalogue
installation created the path registry; SimpleBlog updated to 0.082. Pages and
Webinars were not registered here and were not installed speculatively. Their
shared source is present for subsequent sites. The existing site-specific Apache
REST configuration gained a single-segment content rule after the existing API
aliases, preserving static files/directories. Its previous contents are in the
backup as `routing.conf`. The host proxy and other sites were not changed.

Readable paths enabled and populated for the three published site posts:

- `/chisimba-runs-kenga-learn`
- `/chisimba-runs-learn-the-birds`
- `/chisimba-26-how-we-got-here`

Backfill dry-run, apply and repeat were checked. Old ID links remain valid. The
site's canonical hostname remains `www.chisimba.com`; the bare hostname keeps its
existing redirect. Future title edits retain paths; explicit address edits keep
aliases. New draft addresses remain subject to the publishing access policy.

Recovery retains the prior code and database/configuration backups. Do not restore
an old database over later user writes. Disabling readable paths requires a
coordinated routing/configuration decision because published links may be shared.

Live HTTPS verification passed for all three clean paths, the original ID URL,
the listing's generated links, ignored query overrides, HEAD (200), POST (405)
and an absent path (404). Persistent application logs showed no new errors.
The image's PHP log still emits existing dynamic-property deprecations for
`block_blogcategories`, `block_blogtags` and `block_siteblog` at the unchanged
shared block loader; these are separate maintenance work, not suppressed by this
feature. The full running-container source audit passed with 13,541 files and
read-only source mounts.
