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
