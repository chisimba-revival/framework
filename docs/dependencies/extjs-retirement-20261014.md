# ExtJS runtime retirement — 14 October 2026

ExtJS is removed from the maintained source and mounted local installation.
The retirement manifest is `tests/dependencies/retired-extjs-paths.json`.
It covers the entire core `ext` module (3.0-rc2 and 3.4.0 distributions), the
HTML Elements 3.0-rc2 copy, both PHP loaders, the theme selector and shared ExtJS
skin assets. This removes 5,364 tracked framework files, approximately 84 MB
of working-tree disk usage. Git history retains the retired source.

HTML Elements is now 0.620. Normal catalogue update removes only the obsolete
`extjs_theme` settings belonging to `ext` and `htmlelements`. Its unrelated
configuration is retained. The commented course-home loader is also removed.

## Caller evidence

- Native context-content reports, Triple Store 0.012 and Activity Streamer 0.045
  replace the formerly reachable interfaces. The old Context Instructor was
  retired; Practicals is preserved as `practicals__PAUSED`.
- One additional unreferenced MCQ `resources/js/main.js` still called the removed
  grid helper. No source loader references it; it is removed in the coordinated
  modules change, and the existing MCQ retirement guard covers it.
- Remaining broad text matches are historical registration descriptions,
  unrelated `$extjs` response variables and a MIME-magic data entry, not loaders.
- The new runtime guard scans PHP/JS/CSS/HTML/registration files under framework
  app and the sibling modules checkout when present. At retirement it checked
  9,234 active source files with no runtime references. Tests, docs and paused
  module directories are excluded deliberately.
- A local read-only database scan checked 195 text/content/config columns for
  retired script/style paths; no references were found. This is not a scan of
  production stored content or arbitrary user-uploaded files.
- Fresh read-only registry checks found core `ext` unregistered on local and all
  six production sites. Source filesystem presence is separate from registration;
  the audit's packages-only file flag says nothing about core-module presence.

Paused modules retain their historical source, but cannot be revived against
removed ExtJS. Migrate their complete interfaces to native shared UI first.

## Verification

Retirement guards, native Activity Streamer/Triple Store/report checks, MCQ bank
and question-button checks, context-membership authorisation and lecturer journey
checks passed. Catalogue dependency/hook regression tests passed. Local HTML
Elements update and its cleanup hook passed twice; Activity Streamer's preinstall
hook was also explicitly rechecked. The initial CLI helper omitted catalogue
hooks and correctly failed the theme-cleanup assertion; the helper was corrected
to invoke the same hook sequence as the catalogue UI before the passing reruns.

Local Chrome smoke checks after removal covered course home, MCQ overview and
question banks, Activity Streamer course pages, Triple Store and content-activity
reports. No ExtJS script requests or browser console errors/warnings were observed.
No course records were changed by these read-only checks.

The previously documented stale context-members control-panel contract test is
still unrelated test debt; this retirement does not claim it was repaired.

## Coordinated deployment requirement

**Not deployed to production.** Deploy the matching framework and modules release
together: production's older context-content reports can still require ExtJS even
though the `ext` catalogue entry is unregistered. Include all preceding caller
migrations, normal catalogue language/schema hooks, and this retirement manifest.
Recheck site drift, registrations and stored references before pruning live files.
Do not deploy only the runtime deletion or reactivate dormant modules as part of
it. Activity Streamer recording stays off unless deliberately enabled later.

This completes ExtJS removal in maintained source/local. Remaining frontend work
is the separate Flash/document-preview/client audit and staged jQuery migration.
