# Global jQuery loading gate — 17 October 2026

Global loading is now disabled by default in Skin 0.215. ePortfolio revival remains
on hold; its import form has an explicitly authorised compatibility opt-in in 4.414. This audit
is source/dependency evidence, not permission to activate dormant modules or proof
that every historic feature is safe.

## Completed in this batch

Filters 1.573 retires three Evri Chiki widgets (`evripopver`, `evripost`,
`evrisidebar`), retaining original marker spelling while other commands still
execute. These injected third-party HTTP scripts and used Evri's own namespaced
library; they were a remote-code/security remnant, not proof of a dependency on
Chisimba's global jQuery. No stored marker matches locally or on six production
sites. No external provider was contacted.

Simplefeedback 0.004 removes its unreferenced `simplefeedback.js` demo and
`sample.txt`. No source loaders or stored references were found. Feedback behaviour
was not changed; this does not certify its legacy server workflows.

Tests: PHP syntax and git whitespace; actual Chiki parsing of ordinary, malformed,
Unicode and mixed-case retired markers, including a mixed native firstname command;
existing retired remote filter tests; current Page Notes contracts. Installed
Filters catalogue update and real Chiki rendering passed with warnings treated as
errors. Simplefeedback remains dormant locally. No UI workflow was changed, and no
new browser acceptance claim is made. No production deployment.

## Actual remaining source callers

| Consumer | Scope and disposition |
| --- | --- |
| CMS/CMS Admin | Deferred revival. Both have explicit `SUPPRESS_JQUERY=false` opt-ins; plugin/menu/grid/tree paths still require review. Reusable CMS rendering also matters outside the CMS route. |
| ePortfolio | On hold by request. Its IMS import form explicitly opts in to compatibility loading; broader import security work is documented separately. |
| Blogcomments | Old Blog companion, distinct from SimpleBlog. The noaction template calls its jQuery/JSONP demonstration; save/delete and JSONP routes need review. Preserved as `__PAUSED` by request after the seven-installation usage audit. |
| Stripe | Legacy messaging/status bar, unrelated to payment processing. Its controller loads polling/disclosure JavaScript against Blockalicious. Preserved as `__PAUSED` by request after the seven-installation usage audit. |
| Photostack | Local-album photo gallery, not a Flickr integration. Explicit HTTP jQuery 1.4.2 loader plus client album navigation; ownership/CSRF/file-access review needed. Preserved as `__PAUSED` by request after the seven-installation usage audit. |

Fresh read-only audits found Blogcomments, Stripe, Simplefeedback and Photostack
unregistered locally and on all six production sites, with no corresponding tables.
Blogcomments/Simplefeedback had no broad stored references. Broad Stripe matches
were ordinary text; targeted route/block/JSON-module markers were absent. No
Photostack references were found. These results cover the inspected columns and
installations, not arbitrary archived deployments. No rows were changed by auditing.

The old `pagenotes_DEPRECATED` has disabled registration; current Page Notes passes
its architecture checks. Its archived libraries are not active callers. Native
TinyMCE's internal library code, Prototype dollar calls, comments in inert wrappers,
CSS cache names and the jQuery library implementations themselves must not be counted
as application consumers. Searches included case-insensitive jQuery/loaders and
`$.get`, `$.fn`, `$.ajax`, common chained methods: the wider check exposed Photostack,
which a literal `jQuery` search alone missed.

## Pause validation

All 37 original files across the three modules retain their original bytes.
Registration files are renamed to `register.conf.paused`: a directory suffix alone
does not prevent catalogue discovery. Installed local checks confirmed all three
are unregistered, their original paths are absent, and neither original nor paused
names appear in catalogue discovery. PHP warnings were treated as failures.
No database mutations or production deployment were performed for these pauses.

## Next gate before changing the default

1. Completed: Blogcomments, Stripe and Photostack are preserved as `__PAUSED`; original source is retained for review before any revival. Stripe may be useful with future messaging.
2. Preserve CMS's explicit component opt-ins; test that they do not cause jQuery to
   appear on unrelated pages. Do not revive CMS as a side effect of this cleanup.
3. Decide an explicit, narrowly scoped compatibility boundary for deferred
   ePortfolio, or migrate its import during revival. Do not silently break it or
   imply that a compatibility loader fixes its security issues.
4. In an isolated clone, test the proposed default-off loader, explicit opt-in and
   suppression precedence, and representative login/home/editing/file/course journeys.
   Inspect actual script requests and browser errors, including block composition.
5. Only then change the source default and perform the authorised release checks.

Stopping global loading is separate from deleting every archived library. The
compatibility library and paused source can remain while normal pages stop paying
the runtime cost. The initial audit made no loader change; the follow-up below completes that gate.


## Default-off follow-up — 17 October 2026

Skin defaults `SUPPRESS_JQUERY` to true. A component must explicitly set false
for the current render to load the existing compatibility library. No session
plugin list can override the default. Existing CMS layout and CMS Admin opt-ins
are retained, including component composition outside the CMS route.
ePortfolio's import template alone sets false when the variable is unset; an
explicit true remains respected. Remove that opt-in during the planned native,
security-reviewed importer revival. No import/security behaviour was repaired or
certified by this compatibility change, and no dormant module was activated.

Validation:
- Loader tests cover default-off, explicit true/false, both registered jquerycore
  and fallback wrapper paths, and stale session plugin settings.
- The ePortfolio template test executes its declaration but stops before legacy
  form dependencies, covering unset, explicit true and explicit false.
- An isolated no-outbound database clone exercised the actual CMS layout opt-in
  before its legacy dependencies and rendered the installed compatibility loader.
  Chrome confirmed successful jQuery DOM-ready/text update in that synthetic fixture.
  This is not a full CMS or ePortfolio functional acceptance test.
- Chrome smoke checks: anonymous home, native sign-in, authenticated home with
  composed blocks, homepage editing switch, File Manager selection/clear, course
  home, quick/full Help, course wizard step-one save, TinyMCE at step two and the
  native image-picker window. Inspected script elements contained no jQuery on
  native pages, including after leaving the compatibility fixture. No console
  errors were captured on the inspected pages. Clone PHP logs showed no warnings,
  deprecations or fatal errors.
- The copied home login block attempted HTTPS against the HTTP-only fixture;
  native sign-in succeeded. No browser certificate warning was bypassed. This
  fixture transport mismatch is separate from script loading.
- Syntax/whitespace and both focused tests passed. Local registered Skin was
  updated through Module Catalogue; dormant ePortfolio was skipped. Actual local
  HTTPS home confirmed no jQuery scripts or console errors.

No production deployment. Compatibility assets and archived source remain.
Deploy the framework and module changes together. If a retained component needs
compatibility, scope an explicit opt-in to its render and document its removal;
do not restore global loading merely to hide an untraced dependency.
