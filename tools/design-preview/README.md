# Local LTB content review

A separate, loopback-only preview at `https://chisimba.test:8446/ch/`.
The EcoTraining demo on 8445 and all production sites remain untouched.
This is a design review environment, not a full production clone.

## Snapshot boundaries

`export-public.php` reads only published, unrestricted site blog posts and their
public author names. It exports no production credentials or configuration.
`extract-home.php input.html output.json` extracts the public webinar card grid
from a saved public homepage. Webinar links still open the live public site.
Blog images/videos retain their public source URLs and require internet access.
No subscriber, order, payment, enrolment or private content records are copied.
Public author profiles are inactive and receive new random credentials.

The PHP container has only an internal Docker network. SMTP/sendmail cannot
send, there are no workers, and no production integration keys are installed.
Only the nginx proxy joins an external network, bound to loopback port 8446.
A separate session cookie prevents interference with the existing demo.

## Assembly and setup

Run `python3 assemble.py WORKSPACE --config-template LOCAL_CONFIG_DIRECTORY`.
This refuses an existing preview and archives committed framework/module Git
objects using the common module profile. It excludes private working-tree
changes. The assembly records both revisions in `source.json`.
The runtime is under `dev-environment/runtime/ltb-design-preview`.
Use an existing **local** config template and inspect its paths before use.
The script creates a new local DSN and new installation key.

Set `PREVIEW_ROOT` to that runtime and `PREVIEW_CERTS` to the existing local
certificate directory, then run `docker compose -f compose.yml up -d`.
The database password in Compose is local-only, not a production credential.
Initialise the dedicated database with schema only, then the local framework's
static language, module-catalogue, system-text and permission-definition tables.
Do not import users, permission identities, settings or operational records.

Copy `seed.php`, the public export JSON and homepage JSON into the web
container's `/tmp`. Run with `CHISIMBA_DESIGN_PREVIEW=1` and a locally chosen
`PREVIEW_PASSWORD`:

```
php /tmp/seed.php /tmp/public-content.json /tmp/home.json
```

The seeder requires the dedicated preview DSN and empty users/posts tables.
It provisions username `preview` using the canonical account/bootstrap services.
Then run `configure.php` with the same environment opt-in. This repeatable step
sets local navigation and the post-login destination. It does not modify posts.
Never mount these operators in the public document root.

## Review evidence — 10 November 2026

29 public LTB posts imported. Checked anonymous blog listing, existing article,
local administrator login and populated editor. The long martial-eagle title
uses the available width and expands on a 390px phone viewport without clipping
or horizontal page overflow. Real content exposed stacked mobile gutters; the
shared editorial stylesheet now removes the redundant inner gutter on phones.
Homepage webinar cards are a public snapshot, not an editable webinar database.
Media chooser assets and taxonomy were not cloned.

Review branches: `design/shared-visual-review-20261110` in framework and modules.
No production deployment; common development branch remains unchanged.
