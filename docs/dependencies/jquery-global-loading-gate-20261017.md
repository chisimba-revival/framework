# Global jQuery loading gate — 17 October 2026

Global loading is still enabled. ePortfolio is unchanged and on hold. This audit
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
| ePortfolio | On hold by request. Its IMS import form remains dependent on global jQuery; broader import security work is documented separately. |
| Blogcomments | Old Blog companion, distinct from SimpleBlog. The noaction template calls its jQuery/JSONP demonstration; save/delete and JSONP routes need review. Pause choice pending. |
| Stripe | Legacy messaging/status bar, unrelated to payment processing. Its controller loads polling/disclosure JavaScript against Blockalicious. Pause choice pending. |
| Photostack | Local-album photo gallery, not a Flickr integration. Explicit HTTP jQuery 1.4.2 loader plus client album navigation; ownership/CSRF/file-access review needed. Pause choice pending. |

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

## Next gate before changing the default

1. Resolve the three pending dormant-module choices and trace any retained workflows.
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
the runtime cost. No default or compatibility-loader change was made in this batch.
