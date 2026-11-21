# Local grass guide development installation — 21 November 2026

Authorised target: https://chisimba.test:8445/ch/ only. Status: deployed and verified.
Framework: `674161fdf9f823cad31a51affc0de0cf5c5df68f`.
Modules: `1d3ba53bb47420408d8daab7defa4a031dbea117`.
Manifest: `305e2cd98bc2dd4652e3a0f90031f0fe58a333fba2cf5c0c8b52eaa1cbfa6808`.
Local evidence and protected backups: `work/grassguide-dev-20261121/operator/`.
Runtime: `work/grassguide-dev-20261121/runtime-final/app`, mounted read-only.

Grassguide 0.003 is installed through Module Catalogue and linked from Site
Administration. The Lowveld and Kruger seed contains 179 imported draft species.
The broader savanna list is excluded. Administrators can manage species, evidence,
identification traits, attributed media links and YouTube references. Automated
external harvesting and a public/mobile API are not enabled.

The complete source bundle was assembled from published common-branch Git objects.
The pre-existing local Applications extension was preserved as an unchanged,
explicit 30-file hash overlay; its private source was not committed or published.
Configuration, uploads and database mounts were preserved. Production sites and
the separate LTB preview were not changed.

Initial and repeat catalogue upgrades passed against disposable database clones.
Installed-module tests covered administrator access, anonymous/ordinary-user
denial, repeatable import, editing, help and private export. Browser checks on the
development site confirmed the 179-species catalogue, search, save, contextual
help and final Site Administration navigation. Anonymous HTTP access presents
login without exposing species content.

The final navigation update preserved all 211 protected database tables,
including the imported catalogue. Verification matched all 13,668 running source
files against the complete manifest plus private overlay and confirmed the
read-only source mount. Rehearsal containers and networks were removed. Database
backups and the installed-site screenshot remain in the local evidence directory.
