# Native rounded corners — 15 October 2026

The remaining active source call to jQuery Corner was CMSAdmin's icon panel.
Its JavaScript targeted `.icon` while the panel's shared getCleanBlockIcon helper
actually renders `._icon`. The panel now opts into `chisimba-icon-panel`, whose
shared skin rule applies native border-radius to both icon variants and their
links. Existing permissions, URLs and panel construction are unchanged.

Removed both jquery/jquerycore Corner copies, the old loadCornerPlugin API,
the stale jquerycore catalogue selection and the commented CMS demo. Also removed
the unreferenced Cornerz file and its commented loader; the roundcorners HTML
helper retains exactly its previous output. Unrelated canvas CurvyCorners and
legacy image-based styling are not part of this jQuery plugin removal.

Read-only dbTable scans found zero stored Corner/Cornerz references in relevant
text/configuration columns on all six sites (82/129/81/188/181/181 columns).
CMSAdmin remains unregistered. The scan does not establish that arbitrary dynamic
names or external pages never referenced a retired asset.

Validation passed:

- PHP lint and repository whitespace checks.
- Actual loader/skin regression with stale Corner selections, mixed retained
  plugins, unchanged sessions, unchanged core/UI/theme and valid emitted assets.
- Strict PHP 8.5 rendering of the actual CMS panel method with synthetic
  permission/configuration services and real installed HTML helpers, without
  initialising or enabling the dormant CMS. This exposed an existing assignment
  to htmlTable's unused `align` property, which caused a PHP 8 deprecation. The
  ineffective assignment was removed from this panel method.
- Browser fixture: all 24 icon/link elements had native 8px corner radii; no
  Corner script URL was emitted and no browser warnings/errors were reported.
- Normal local catalogue updates of already-registered jquerycore, htmlelements
  and skin. No dormant module was enabled. Disposable browser fixture removed.

Versions: jquery 0.058, jquerycore 0.014, htmlelements 0.621, skin 0.211,
CMSAdmin 1.650. Remaining jquerycore plugins: Validate, Form, LiveQuery, oEmbed.

This is a cosmetic dependency migration, not a revival or complete security
review of the old CMS. Its broader legacy dependencies remain separate work.
