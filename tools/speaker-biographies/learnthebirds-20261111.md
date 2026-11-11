# Learn-the-Birds deployment — 11 November 2026

Committed and pushed on the common `release/consolidation-20260930` branch
before creating the complete source bundle:

- Framework: `66c34e6b73c9b561b3129a320840cf16eb5e9182`
- Modules: `08a17062cbfe180d9162ec94057f931a8f5f2953`
- Complete source manifest: `75fb91116a67d2f605a09cff3d47e5662c2c8286d48fd2612ecababf9b33f686`
- Operator evidence: `/srv/speaker-layout-wrap-learnthebirds-20261111`
- Backup: `/srv/learnthebirds.com/backups/shared-20261111-061729`
- Release: `/srv/learnthebirds.com/releases/common-dev-20261111-061729/ch`

An earlier layout candidate was rehearsed but never deployed; the user's
additional text-wrap request was incorporated and the complete final rehearsal
repeated. The final isolated rehearsal ran registration twice, verified native
PDO/authentication/translation/catalogue behaviour and checked public home,
login and password-reset pages. All 116 protected data tables were unchanged.

Deployment checked the prior complete manifest, paused the mail timer, stopped
writers, verified database/configuration backups, switched source read-only,
registered Webinar 0.031 and verified existing content/navigation fingerprints.
The mail timer resumed active. No new fatal/SQL errors were found in persistent
application logs. No other site was deployed.

Read-only live browser checks of Hugo Sampaio's biography confirmed the embedded
portrait remains 300 × 225, floats with a 20px side gap and the separate logo is
257 × 182 in the right panel. The speaker popup uses the same composition and
Escape restores focus. No production speaker was saved or edited during checks.

Final host/container audit passed all 13,547 shared files, with no missing,
changed or extra source, unexpected source mounts or stopped web container.
The fleet record retains the explicitly deferred sites and private-code exception.
