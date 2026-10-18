# Optional plugin retirement checks

Run from the framework checkout:

```sh
php app/core_modules/jquerycore/tests/plugin_retirement_test.php
```

This standalone test runs the actual skin forwarding and core loader against the
real XML catalogue and asset paths, with isolated service/session doubles. PHP
warnings are failures. It checks stale-only and mixed plugin selections, the
empty plugin catalogue, retired Validate selections, duplicate selections, both setter APIs, unchanged session data,
core/UI/theme selection, suppression and emitted asset existence. It needs PHP
with SimpleXML and does not use a database or a real user's session.

The removed jquerycore plugins are tooltip, Flexigrid and tooltip-only bgiframe,
delegate and dimensions, plus the Internet Explorer PNG fix, Corner, oEmbed,
LiveQuery, Form and Validate. Older copies of the
tooltip/Flexigrid families and UI/htmlelements helpers are separate dependencies;
the older jquery PNG-fix copy has also been retired. All LiveQuery copies and wrapper loads have since been retired.
Form had no
submission/serialization API callers; the redundant ShortURL load was removed.
