# Automatic jQuery loading rehearsal — 16 October 2026

## Decision

Keep automatic loading enabled pending migration of shared callers. This was a
bounded browser experiment, not certification of every module or permission role.
No application source or production configuration was changed.

## Method

An isolated disposable PHP 8.5/PDO clone of the local database used read-only
source mounts and an overlay setting `skinjavascript::getJQuery()` suppression
to true. Its database was in memory; outbound networking and workers were absent.
Uploads were not copied. Chrome tested the loopback HTTP fixture. Native sign-in
worked; the older sidebar login attempted HTTPS on the HTTP fixture port, so it
was not used. No browser security warning was bypassed.

Rendered script lists confirmed the automatic jQuery and jQuery UI assets were
absent. Restoring loading in the overlay produced core 1.7.2 and UI 1.8.18 for the
comparison. The active local installation and production retained normal loading.

## Observations

| Surface | Exercised without automatic loading | Result |
| --- | --- | --- |
| Native sign-in | Sign in to disposable administrator account | Passed |
| Shared navigation | Open notifications | Expanded successfully |
| Personal Space | Enable editing, show block controls, open quick Help | Passed; no new console error |
| Calendar | Render October and navigate to November | Passed; empty-month fixture, no event writes tested |
| File Manager | Render listing, switch Grid to List, open New folder | Controls worked; separate script syntax error below |
| Homepage | Render blocks, activate Turn Editing On | Editing switch did not change state, also with normal loading |

The anonymous homepage emitted `ReferenceError: jQuery is not defined` from
`navigation/classes/featurebox_class_inc.php::loadToggleScript()`. This binds
`.toggleIt` and animates `.featureboxcontent`; migrate the complete disclosure
behaviour, including keyboard support, rather than suppressing the error.

Source tracing also found the jQuery-based `canvas/resources/canvasblocks.js`,
returned by `canvas/classes/buildcanvas_class_inc.php`. That caller was not
exercised in this browser sample. Modern Personal Space instead loaded the native
`context/resources/contextblocks.js`. CMS dependencies remain intentionally intact.

## Separate anomalies requiring investigation

* Homepage Turn Editing On remained unchanged with automatic loading both off and
  on. Its older markup and native context block-editor integration need tracing.
* File Manager emitted `SyntaxError: Unexpected end of input` with loading both
  off and on. Grid/List and New folder still worked. The source of that error has
  not yet been established; it must not be labelled a jQuery-removal regression.

These comparisons concern the disposable clone, not proof of identical behaviour
on production. No saves, deletes, uploads, calendar event changes, Kanban edits,
Knowledge Map edits, or image-picker workflows were certified by this sample.

## Next work

1. Trace and repair the two baseline anomalies with focused regression checks.
2. Replace the shared feature-box toggle with native disclosure behaviour.
3. Trace and migrate the remaining canvas editor caller, preserving its security,
   stale-state, draft, and failure contracts.
4. Repeat and broaden this no-jQuery rehearsal before designing conditional
   loading. Include embedded blocks and editors, not only top-level module routes.
