# Native document and picture-show cleanup — 14 October 2026

Source and local installation only. No production deployment in this slice.

## Changes

- File Manager 1.109 no longer converts PDF/Office documents into SWF or reads
  generated Flash HTML wrappers. PDF preview uses a titled browser iframe plus
  an original-file link. Office formats, including docx/xlsx/pptx, offer the
  original file. The native File Manager PDF panel uses this shared preview.
- Original files and existing derivatives are not deleted. Canonical File Manager
  delivery rechecks permissions; the preview also checks the shared read policy.
  No public copy or new conversion service is introduced.
- Removed SWFUpload's unused helper and both copies of its Flash binary, plus the
  orphan presentation picker which passed server paths to a Java applet callback.
  No source callers remain. The referenced documentconverter/JODConverter and
  swftools packages are absent from both maintained repositories; no server Java
  runtime was removed.
- Filters 1.568 retains `[PICTURESHOW:username=admin,folder=pictures]` as a native
  linked image gallery. Parameters reset for each shortcode. The gallery queries
  registered files in the exact personal folder using bound values, checks each
  file's current read policy and uses canonical delivery URLs. It does not scan
  arbitrary directories, create data.js, use Flash or change body onload handlers.
  Unknown users, invalid paths, inaccessible files and empty folders render no
  gallery. Only browser raster formats are included; originals remain available
  through File Manager. Unregistered historical images must be indexed through
  File Manager before appearing; there is intentionally no raw-storage fallback.
- Removed Runway assets and TinyMCE's obsolete Flash plugin. Removed its context
  menu action from both vendor source/distribution variants (a local vendor patch).
  Modern htmlarea uses TinyMCE 8 and is unchanged. Legacy tinymce is now 0.011.
- Companion modules change removes the unused ChisimbaLangTranslator desktop Java
  source/build/JAR and download link; langadmin 0.009 retains its existing PHP
  functions and data. The same-day registry audit found langadmin unregistered
  locally and on all six production sites. Current core language administration
  does not use that desktop client.

## Verification

- PHP lint, JavaScript syntax and whitespace checks passed.
- `native_document_preview_test.php`: PDF and seven document formats, canonical
  URLs, escaping, denied preview, gallery access, traversal and retired paths.
- `native_gallery_pdo_test.php`: real PDO queries and real anonymous read policy;
  public image included, hidden image excluded; synthetic rows rolled back.
  Run from a local installation root with `CHISIMBA_NATIVE_PREVIEW_TEST=1`.
- File-access policy (including revoked owner), download/attachment, delivery,
  derivative mapping, native image picker and download-picker tests passed.
- ExtJS regression guard passed across 9,217 active source files.
- Local database scan: 195 candidate content columns, no SWFUpload, PICTURESHOW,
  applet callback or desktop translator references. Production stored content
  was not scanned in this slice.
- Chrome: native File Manager, disposable PDF detail, shared PDF frame and fallback
  link; 390px viewport with no horizontal overflow. Chrome displays its own Open
  fallback for embedded PDFs here, so inline PDF page rendering is not certified.
  One Chromium PDF warning (`crbug/1173575`) appeared; no application console errors.
  No application PHP warnings/deprecations observed. Gallery layout was not separately
  exercised in the browser; its rendering and policy were checked behaviourally.
- PDF fixture removed. Initial cleanup assertion used dbfile's cached record and
  falsely reported a remaining row; a fresh database process confirmed no row.
  No user content or schema changed.

## Release and follow-up

Deploy matching framework/modules sources with normal catalogue updates and the
retired-path manifest; retain original documents and old generated derivatives.
A source deletion is not proof of deletion on a deployed host. This slice is not
an assertion that every historical Flash reference has gone: ZeroClipboard,
OpenFlashChart and optional jQuery plugins still need caller tracing during the
staged JavaScript audit. Paused modules remain preserved for future review.
