# Shared password visibility — 24 November 2026

MyHairPlanner receives the common skin password visibility control. The eye
button uses UI iconservice and translated labels; it supports Ajax-added inputs,
keyboard activation, Escape, masking on submit/reset and when the tab is hidden.
Without JavaScript the original masked password input remains usable.

Common source: framework `857e735633829b6dfdbfce4a2360bd7ad07d6621`,
modules `3be18b6e240a0787b665d6f3d4e8dad3c94e771e`.
Manifest `f7b20559ddfe5bcda513081bce49eec957f9f6ba9911e4864c38e03fc5804066`.
Operator and private verification records:
`/srv/myhairplanner.com/password-release-20261124-v2/`.

The prior November 23 private releases were reviewed against the older approved
manifest. Generic personal-account provisioning in userservice and
userprovisioningservice was reconciled into the common framework. All 47 private
module/API files remain outside GitHub and are separately hash-inventoried.
The private REST routing adapter was rebased onto current shared REST code so
new shared endpoints and existing private routes are both retained.

The full release and subsequent tooltip correction passed isolated database
rehearsals, repeated catalogue upgrades, native service checks and homepage,
login and recovery HTTP checks. All 96 protected tables were unchanged.
Browser tests covered reveal/hide, Escape, reset and dynamically added fields;
desktop and 390px phone layouts were inspected. Provisioning checks preserve
the surname requirement for ordinary accounts while allowing personal accounts.

Only MyHairPlanner is authorised for this password-control deployment. KengaLearn
retains its separately verified photo-batch release recorded in
`20261124-kengalearn-photo-batches.md`; other sites retain their existing approved
manifests. These known version differences are a staged development rollout,
not permission to silently replace live code or private inventories.

Final live checks confirmed translated Show password/Hide password labels and
successful administrator sign-in. The account identifier and generated credential
are not stored in this repository. Final deployment backup:
`/srv/myhairplanner.com/backups/shared-20261124-082010/`.

Final fleet audit: all seven source/container/private-inventory checks passed.
The auditor reports the already documented staged-version differences against
its oldest fleet-wide pins; no unexplained source differences remain.
