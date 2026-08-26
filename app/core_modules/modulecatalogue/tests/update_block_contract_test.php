<?php
$root = dirname(__DIR__);
$block = file_get_contents($root . '/classes/block_updates_class_inc.php');
$controller = file_get_contents($root . '/controller.php');
$script = file_get_contents($root . '/resources/updates.js');
$template = file_get_contents($root . '/templates/content/updates_tpl.php');
$manifest = file_get_contents($root . '/register.conf');

$checks = array(
    'native browser requests replace jQuery' => str_contains($script, 'fetch(url, {')
        && !str_contains($script, 'jQuery')
        && !str_contains($script, '$.ajax'),
    'success depends on server response' => str_contains($script, '!response.ok || !payload.ok')
        && str_contains($script, "payload.message"),
    'failures remain actionable' => str_contains($script, "button.disabled = false")
        && str_contains($script, 'payload.csrfToken'),
    'requests are same-origin posts' => str_contains($script, "method: 'POST'")
        && str_contains($script, "credentials: 'same-origin'"),
    'controller verifies csrf' => str_contains($controller, 'validUpdateRequest(')
        && str_contains($controller, '->consume($context,'),
    'controller returns bounded json' => str_contains($controller, 'sendUpdateJson(')
        && str_contains($controller, "X-Content-Type-Options: nosniff"),
    'stale updates cannot be applied' => str_contains($controller, 'pendingUpdateMatches('),
    'normal catalogue uses protected forms' => substr_count($template, 'name="csrf_token"') >= 2
        && str_contains($template, 'method="post"'),
    'block uses valid semantic controls' => str_contains($block, '<article class="module-updates__item"')
        && str_contains($block, '<button type="button"')
        && str_contains($block, '<img class="module-updates__icon"')
        && !str_contains($block, "createElement('image')"),
    'legacy selectors are gone' => !str_contains($script . $block, 'patchLink')
        && !str_contains($script . $block, 'linkUpdateAll')
        && !str_contains($script . $block, 'div_updates'),
    'module version records replacement' => str_contains($manifest, 'MODULE_VERSION: 3.129')
        && str_contains($manifest, 'timer-driven legacy jQuery'),
);

foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo 'OK: ' . count($checks) . " module update block checks\n";
