# Configurable site navigation

The existing **Site administration → Configure navigation / Configure Module Links** editor owns navigation. There is no parallel JSON link store or module provider registry.

The existing `TOOLBAR_TYPE` setting selects the layout. `dropdown` remains the default learning navigation. `site` renders a visitor-facing menu for both anonymous and authenticated users. Existing flat and legacy learning options remain available. Chisimba Reborn supplies the appearance; the LTB canvas supplies branding.

## Editing

1. In Configure Module Links, choose **Site navigation** as the toolbar layout and save.
2. Select the module which owns a destination. Add or edit its link under **Site navigation**.
3. Supply the module action, icon identifier and language code. Labels use the language and system-text services. Change wording with Language Text rather than hard-coding a label into a route.
4. Positions range from 0 to 999 and sort numerically. A shared group language code collects links into a disclosure menu; an empty group leaves a direct link.
5. Choose permissions deliberately. An empty right is explicitly public. Administrator-only and context-dependent flags still apply, as do the destination's own access checks.
6. Save and review as a visitor as well as an administrator. Input validation preserves entries. Removing a link does not remove content.

An administrator's account menu always retains Site administration and Configure navigation, even if every configurable link has been removed. Switch back to the learning layout to recover that toolbar. Restore Defaults replaces *all* customised links for the selected module, following the existing registrar contract.

Contextual Help and its full guide are available in the editor. Menus use native `details` disclosures, keyboard activation and Escape/focus return. Without JavaScript, the initially expanded menu remains usable. Mobile presentation uses the same destinations and permissions.

## Storage and registration

`tbl_menu_category` remains authoritative. Site entries use:

```
site_NNN|registration-access|action|icon|language_code|optional_group_language_code
```

The existing 120-character category limit applies. The editor validates bounded identifiers and length before writing. The permissions column retains the resolved canonical right; the registration access segment is not an access decision at render time.

Modules declare default links through the registrar, for example:

```
SITE_NAV: 010|site|archive|calendar|mod_webinar_title
```

The existing scoped-registration suffix and canonical grant resolution apply. The reader recognises `SITE_NAV`; the registrar uses the same storage and permission APIs as other menu declarations. No schema migration is required. Existing learning menu queries exclude site declarations.

`toolbar/navigationservice` resolves installed, authorised destinations; `toolbar/sitenavigation` supplies semantic markup and canonical account controls. The skin selects the site renderer for authenticated and anonymous requests. Suppression flags still apply. Authentication carries the requested relative page through the existing validated return-target mechanism; Logout remains POST plus CSRF.

Webinar suppresses its local catalogue navigation only when all three routes are supplied by the active site menu. Other layouts retain the local menu.

## Verification

Run `app/core_modules/toolbar/tests/site_navigation_behavior_test.php` and `tests/nativeauth/toolbar_canonical_*_test.php`. Browser verification must cover profile switching, actual editor save/reload, groups, anonymous/admin views, invalid input, denied CSRF/anonymous writes, mobile width and keyboard behaviour, and Help.

Deployment must include the framework changes and Webinar together. Update toolbar and webinar registration/language metadata after assembling the runtime. Enabling the site profile is a per-site administrator choice; it is not a new global default.
