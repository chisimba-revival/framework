# KengaPub paid-order notifications — 17 November 2026

Authorised target: KengaPub only. Status: deployed and verified.
Other sites retain their individually approved manifests. MyHairPlanner private
source remains under its unchanged exact inventoried exception.

Framework: `7eaf5db18228abc34efea84c24ed8e64ed5318dd`.
Modules: `d3f11a6008ecd2e58bcce4860b8dea50a33477a0`.
Manifest: `6ffaac309d0e4749e0cf88051d23183cccd38ffef875a46f712eb302137aca20`.
Evidence: `/srv/kengapub-paid-notifications-20261117`.
Backup: `/srv/kengapub.com/backups/shared-20261117-130404`.

Shop 1.018 adds up to 20 paid-order notification addresses in Shop settings.
Addresses require no accounts. Each verified payment snapshots its recipients;
recipient-specific queue keys prevent duplicates on fulfilment or notice retry.
Emails contain items, total, reference and an authenticated manager link, never
the customer's private order token. Existing paid orders are not backfilled.
The setting is blank on KengaPub, ready for the owner to configure. No test mail
or payment was sent against production.

Validation: synthetic service and rendering checks, all relevant existing shop
unit checks, and real PDO/dbTable tests on a disposable local database passed.
Server-side isolated database rehearsal passed both initial and repeat upgrades,
registered labels, disabled default, public homepage/login/reset probes and
business data/navigation comparisons. Production preserved 229 protected tables
and existing navigation; five published book QR-code pages returned HTTP 200
with identical audio links before and after deployment. Full host/container
source audit passed all 13,595 files and mount checks. No new runtime error
indicators were found; the KengaPub mail timer resumed and is active.
Browser automation was unavailable; form rendering and escaped draft retention
were tested with synthetic fixtures rather than a fresh browser session.

The complete shared source also brings KengaPub's installed modules up to the
common versions: contextadmin 1.436, filemanager 1.118, toolbar 1.822,
myadmin 0.112 and simpleblog 0.085. No site-specific source patch was used.
