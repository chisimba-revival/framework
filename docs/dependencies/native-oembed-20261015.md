# Native media consumption — 15 October 2026

Replaced the old jquerycore oEmbed plugin and the oembed module's three JavaScript
copies/stylesheet with a native consumer. Quickembed, remotewall, Wall's initial
content and dynamically inserted replies now use the native consumer. No known
source calls to the old jqoembed class or jQuery `.oembed()` remain.

The consumer preserves the original link and offers an optional Show media button.
Recognised provider URLs reuse contentblocks/contentmediaservice (YouTube, Vimeo,
TikTok and Xeno-canto); direct HTTPS images/audio/video use native browser elements.
Unsupported URLs remain ordinary links. A pure same-origin POST resolver performs
URL classification only: no server-side fetching, remote HTML, JSONP, provider
API requests, local-file metadata lookup or stored-content reads. It rejects
unsafe schemes, credentials, control characters, overlong input and malformed
array query parameters. HTTP direct media remains a link to avoid mixed content.
Provider/file access remains controlled by the source when the browser loads it.

Source tracing also found public legacy publishing actions with filename-based
metadata lookup and no modern file-access policy check, plus a provider for the
paused Blog module. These routes now return HTTP 410 before initialising provider
services. Derek approved retirement on 15 October 2026; the six obsolete provider
classes and five obsolete output templates have been removed. This is separate from the native media consumer.

All six sites have oembed and Wall unregistered, with Filters registered. Read-only
dbTable scans of relevant text/configuration columns found zero stored oembed,
quickembed or remotewall references (82/129/81/188/181/181 columns). This does not
prove absence of external clients, arbitrary aliases or encoded references.
No dormant module was enabled and no stored content was rewritten.

Local checks passed:

- Strict native-consumer/controller/filter tests, hostile inputs, supported
  providers/media kinds, escaping, duplicate loads, registration gating and 410s.
- Loader regression for stale/mixed oembed selections, retained plugins, unchanged
  sessions, configured core/UI/theme and emitted asset existence.
- PHP syntax, JavaScript syntax and repository whitespace checks.
- Actual anonymous POST resolver returned a normalised descriptor without an
  external request; the legacy provider route returned HTTP 410.
- Browser demo loaded an existing local image by keyboard, retained the original
  link and moved focus to the preview. Contextual Help, full guide and Escape passed.
- A jQuery-free fixture checked overlapping selectors (one button per link),
  unsupported-link fallback and a newly inserted Wall-style reply. Its image
  loaded successfully after activation; all original links remained. Layout fit
  a 390px viewport. An initial CLI fixture URL-context error was corrected before
  these checks; it did not occur in the actual demo route.
- Normal local catalogue updates of already-registered jquerycore, Filters and
  Skin passed. The disposable fixture was removed.

Wall's other jQuery behaviour and its legacy write endpoints are not migrated or
certified by this change. There was no live Wall mutation test. External provider
playback was not triggered; descriptor conversion was tested with synthetic URLs.
Three jquerycore plugins remain: Validate, Form and LiveQuery.

Production-image rehearsal additionally exposed three existing PHP 8.5 switch-label
deprecations in Wall. Replacing the semicolons with colons removed them without
changing the branch behaviour. Full deployment evidence is in
[the rollout report](../native-oembed-rollout-20261015.md).
