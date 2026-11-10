# Design and publishing update — 10 November 2026

Deployed to Chisimba.com and Learn-the-Birds only, as authorised. Other sites
remain on their recorded versions pending the owner’s later review.

Runtime source: framework `e87f9c341b5fd7703b19eff09d413d1968972149`,
modules `cccb6a3b5238fc7cd01355869f1f9f22fc6b7317` on the common
`release/consolidation-20260930` branch. Both were pushed before deployment.

Includes the approved Chisimba Classic canvas, shared image-only block with
caption, and shorter site publishing heading `Site [-blog-]` using configured
terminology. Learn-the-Birds retains its approved canvas.

Both previous source manifests passed audit. Module registration was rehearsed
twice on isolated database copies. Chisimba’s rehearsal initially detected
normal expiry of an online-presence row during HTTP checks; the operator now
distinguishes that transient table from protected business data (commit
`ba5f537ff`). The repeated rehearsal passed. No live database rollback occurred.

- chisimba.com: backup `/srv/chisimba-com/backups/shared-20261110-131129`; 130 protected tables unchanged.
- learnthebirds.com: backup `/srv/learnthebirds.com/backups/shared-20261110-131037`; 114 protected tables unchanged.

Both deployments passed home, login and password-reset checks. Full source
audits verified all 13,537 files on the host and inside each running container;
no unexpected source overlays were found. Desktop and 390px browser checks
confirmed the live pages render without horizontal overflow; Chisimba displays
“Site news”. Focused image-block and canvas contract tests passed.

The fleet inventory records exact per-site manifest paths and hashes. This
record and the operator correction are operational metadata; they do not alter
the runtime source manifest. Private adapters and unrelated local tenancy work
were excluded from this deployment.
