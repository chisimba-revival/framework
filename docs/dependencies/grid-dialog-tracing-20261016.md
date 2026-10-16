# jqGrid and Boxy follow-up — 16 October 2026

Both source repositories resumed clean at framework 87cc49069/modules 6fd20b4eb.
Local PHP 8.5 web/database/nginx containers are running after the reboot. The
actual mounted runtime passes jquerycore/tests/plugin_retirement_test.php and
jquery/tests/dynamic_events_test.php, including stale plugin selections, empty
catalogue, emitted assets, script-safe dialog values and delegated handlers.
These are PHP tests, not browser acceptance. Chrome is not connected to browser
automation; the in-app browser rejects the local certificate. Browser checks
remain pending; no certificate warning was bypassed.

## Confirmed source callers

- ShortURL constructs jqgrid and Boxy in its UI. Its grid display is commented
  out, but attachDeleteEvent is active in mapping confirmation dialogs. Do not
  remove jqGrid merely because show() is commented. Boxy handles add/edit/delete.
- CMS Admin constructs Boxy in its controller/display/utilities and uses it for
  permissions, flags, email alerts and denial notices. The section-grid template
  calls jqGrid, although the controller's return of that template is commented.
- Forms uses Boxy for its filter-code dialog. This is distinct from Formbuilder.
- CMS declares CMS Admin, which declares ShortURL. Preserve this dependency chain
  until a coordinated pause or migration decision is made.
- Shared parse4form only constructs Forms' dbforms if registered. parse4chiki
  still exposes a CMS link helper. Registration alone is not full caller proof.

## Security and next decision

ShortURL's controller permits logged-in callers to reach editmapping,
deletemapping and grid edit actions without explicit administrator, method or
CSRF checks; the called repository mutators do not supply those checks. Login is
not sufficient authorisation. Do not enable it on a working site for testing.
User was asked whether CMS/CMS Admin, Forms and ShortURL should be preserved as
paused modules or modernised now. No pause, activation, library deletion or
production change has been performed in this trace.

Local catalogue checks find all four unregistered, with zero stored matches for
jqgrid/jqboxy/plugin paths, module=cms/module=shorturl and [form: in the inspected
text/relevant varchar columns. Counts do not prove absence of encoded references,
other syntax, filesystem configuration or external callers. Six-site receipts
are collected separately in work/grid-dialog-trace-20261016.

The completed six-site scan also found all four modules unregistered and zero
matching stored references (125/174/124/244/237/237 columns). Local maintained
Pagenotes contract tests passed as well. Browser acceptance still pending.

## Decision and first security checkpoint

Derek chose modernisation now for CMS/CMS Admin, Forms and ShortURL; the earlier
pause question is resolved. Start with ShortURL's request boundary, then its
native editor/repository before the other callers. No module has been paused.

ShortURL 1.011 requires current persisted administrator membership for every
management action, uses an explicit action allowlist, validates scalar IDs/URL
and dynamic-key field formats, and requires POST plus the shared login-bound
CSRF token for writes. Existing add/edit/delete forms carry tokens. The public
redirect remains public and no longer constructs the administration UI.
The list already renders as HTML; the jqGrid display was commented out but its
row-deletion handler was active alongside normal form submission. Removed that
duplicate handler and redundant grid loading. Boxy is still required.

Standalone policy and actual controller subprocess tests pass in PHP 8.5 with
warnings as failures. Rejected requests make zero repository calls; accepted
administrator deletion delegates once. The probe exposed undeclared controller
properties; those are declared. These tests do not certify legacy persistence
or a complete working editor. Native UI/draft recovery, escaping, prepared and
transactional repository writes, revision checks, Help and installed acceptance
remain before enabling ShortURL. No production deployment or activation.

Chrome reconnected: local homepage and Personal Space render; editing toggle
opens/closes controls, compact/full Help works, Escape returns focus to its
opener. Browser warnings/errors captured were empty; loaded scripts contain core
jQuery/UI and maintained client code, not Validate or Flexigrid. Container logs
for the smoke interval contain zero PHP warning/deprecation/fatal/parse messages.
This closes the blocked basic smoke for retired assets, not full ShortURL browser
acceptance. No placements/content were modified.
