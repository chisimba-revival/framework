# jQuery Form plugin retirement — 15 October 2026

Removed all three bundled jQuery Form copies: jquery/resources/jquery.form.js,
jquery/resources/plugins/form/2.12 and jquerycore/resources/plugins/form/3.0.8.
Removed the jquerycore catalogue entry and jquery::loadFormPlugin. ShortURL's
redundant loader and Forms' commented loader were removed. This does not remove
the Forms module or change application form submission endpoints.

Source tracing, including minified resources, found no application calls to
ajaxForm/ajaxSubmit or Form serialization APIs. ShortURL uses its existing form
and jqGrid submission paths. Validate contains an optional guarded resetForm
integration, not a hard dependency; no ShortURL/Boxy/jqGrid resetForm callers were
found. Vendor APIs with similar names (TinyMCE, Zend, form-builder helpers) are
unrelated and unchanged.

Read-only dbTable scans on all six sites found no stored references to jquery.form,
loadFormPlugin, ajaxForm, ajaxSubmit, plugins/form/, formToArray, fieldSerialize or
formSerialize in inspected columns (82/129/81/188/181/181). This does not prove
absence of encoded references, external clients or filesystem sessions. The
actual skin/loader regression covers stale sessions without changing their data.
ShortURL and Forms remain unregistered; no dormant module was enabled.

Host and local PHP 8.5 tests passed: stale/mixed selections, retained Validate,
duplicate selections, setter APIs, unchanged session/core/UI/theme, suppression,
emitted asset existence and native Grid/Boxy wrapper output. The normal local
jquerycore catalogue update passed. Local homepage and Personal Space browser
smoke checks passed with no console warnings/errors; no Form resource was loaded.
No live form was submitted or stored content changed, and no new user journey
or Help requirement was introduced. Legacy module security is not certified here.

Validate is now the only jquerycore catalogue plugin. Its callers include
Textblock, Slate, Switchboard, OER and OERuserdata, plus separate Formbuilder and
deprecated Pagenotes copies. That is a separate behavioural migration; native
required/email validation, custom rules, remote checks and error placement must
be traced before changing it. Global jQuery/UI and legacy widget libraries remain.

[Six-site deployment evidence](../form-plugin-rollout-20261015.md).
