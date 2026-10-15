# Optional plugin retirement checks

Run from the framework checkout:

```sh
php app/core_modules/jquerycore/tests/plugin_retirement_test.php
```

This standalone test runs the actual skin forwarding and core loader against the
real XML catalogue and asset paths, with isolated service/session doubles. PHP
warnings are failures. It checks stale-only and mixed plugin selections, all six
retained plugins, duplicate selections, both setter APIs, unchanged session data,
core/UI/theme selection, suppression and emitted asset existence. It needs PHP
with SimpleXML and does not use a database or a real user's session.

The removed jquerycore plugins are tooltip, Flexigrid and tooltip-only bgiframe,
delegate and dimensions. Older jquery/module-bundled copies and UI/htmlelements
helpers are separate dependencies and are outside this retirement.
