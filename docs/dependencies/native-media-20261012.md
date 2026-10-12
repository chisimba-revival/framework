# Native media player migration — 12 October 2026

## Behaviour

The shared `files/nativeplayer` service renders native `<audio>` and `<video>`
controls, with no autoplay or external player scripts. It preserves supplied
file-serving URLs and their query strings; it neither looks up file paths nor
changes ownership, visibility or download authorisation. Callers remain responsible
for authorising the URL. URL schemes and attributes are validated/escaped through
the existing rich-text URL policy and checked numeric dimensions.

An ordinary visible "Open media file" link remains outside every player, so it
is available even when the browser supports the element but not the file's codec.
FLV, SWF, WMV, AVI and MPEG files receive a conversion notice and the original URL
rather than a plugin. No uploaded files are converted, deleted or rewritten. A
file extension alone does not guarantee codec support; H.264 MP4, WebM and browser
supported audio are the practical upload choices. Existing protected URLs remain
protected by their original routes.

Migrated paths:

- File Manager audio, MP4/M4V, WebM, Ogg, FLV, QuickTime and legacy Windows media.
- `[MP3]`, `[FLV]` and anchored `[EMBED]` filters. Plain/pasted MP3 URLs now both
  work; FLV parameter parsing preserves `=` inside query strings and cannot leak
  a previous URL into a later incomplete shortcode.
- HTML5 Elements video wrapper and oEmbed podcast HTML.
- Paused Stream wrapper: native rendering only; it remains paused and was not
  activated. Its URL is no longer decoded a second time or defaulted to an old IP.

Removed Flowplayer, Java SoundPlayer/TinyPlayer/Cortado, Flash MP3, old Video.js
and their local fallback/demo bundles. See `tests/dependencies/retired-media-paths.json`.
The three obsolete core player builder classes are removed after caller migration.
Historical File Manager method names remain native compatibility entry points.

Google Video, MySpace video, Sketchcast, SlideShare and WPresent filters contained
Flash-only provider embed implementations. Their parsers now preserve input text
without service/network loading. Owners can identify their old shortcodes and
replace them with native media or maintained provider embeds. No new provider URL
or API is guessed, and saved page content is not rewritten.

## Registration and local validation

Versions: Files 0.438; Filters 1.566; File Manager 1.107; HTML5 Elements 0.002;
oEmbed 0.085; paused Stream 0.041. Files owns the new translated fallback text;
callers declare Files and Files declares Utilities. The shared skin owns responsive
player sizing for every canvas.

The normal catalogue update was applied locally to Files, Filters and File Manager.
HTML5 Elements/oEmbed were not installed and were not activated for testing.

Passed:

- Strict PHP 8.5 native-player and module-wrapper behavioural tests: URL/attribute
  injection, original protected routes, query strings, shortcode forms, incomplete
  input, legacy format links, no plugin markup and no accidental autoplay.
- Retirement guards and remote-filter preservation tests.
- All five File Manager tests. Corrected a pre-existing exact-version assertion
  in the generated-asset test to assert its minimum supported version.
- File access policy test: 50 assertions plus revoked-owner hidden-file check.
- Changed PHP lint and whitespace checks.
- Installed engine object construction and registered language lookup.
- Chrome synthetic H.264 MP4 and silent WAV: both reached two seconds, media error
  null, controls enabled and autoplay false. At 390 pixels no horizontal overflow.
- Browser File Manager smoke check; no PHP warnings/deprecations/fatals/notices in
  the local container log window. Disposable web fixtures removed after checks.

## Remaining scope and deployment

This removes the traced media-player bundles, not every Flash/Java-related feature.
FreeMind rendering, the OBJ 3D viewer, LiveChat applet, RTT desktop client and
Runway/editor-era interactive assets need separate caller and content decisions.
MIME type recognition for historical files is intentionally retained. Paused or
uninstalled modules still require a full functionality/security review before use.

Committed source/local runtime only; production deployment is pending. Deploy the
framework and module changes together, including the shared skin and registration.
Prune removed resources in a fresh release, update installed modules through the
catalogue, preserve uploaded originals and caches, and rehearse representative
site content and protected-media access on a disposable clone before rollout.
