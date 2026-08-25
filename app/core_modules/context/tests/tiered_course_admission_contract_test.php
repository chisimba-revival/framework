<?php
/** Compatibility contract for opt-in tiered course admission. */
$module = dirname(__DIR__);
$read = function ($relative) use ($module) {
    $content = file_get_contents($module . '/' . $relative);
    if ($content === false) { throw new RuntimeException('Unable to read ' . $relative); }
    return $content;
};
$expect = function ($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
};

$schema = $read('sql/tbl_context.sql');
$updates = $read('sql/sql_updates.xml');
$database = $read('classes/dbcontext_class_inc.php');
$controller = $read('controller.php');
$template = $read('templates/content/needtojoin_tpl.php');
$overview = $read('classes/studentlearningoverview_class_inc.php');

$expect(strpos($schema, "'access_policy'") !== false, 'Context must own the admission policy.');
$expect(strpos($updates, '<name>access_policy</name>') !== false, 'Existing installations need an additive policy migration.');
$expect(strpos($database, "&& trim((string) \$line['access_policy']) !== ''") !== false,
    'Only deliberately mapped courses may invoke tiered admission.');
$expect(strpos($database, "elseif (\$line ['access'] == 'Private')") !== false,
    'Null policy must retain the legacy private membership path.');
$expect(strpos($database, "'resourceType' => 'course'") !== false
    && strpos($database, "'resourceId' => \$context['contextcode']") !== false,
    'Mapped admission must use the shared course policy contract.');
$expect(strpos($database, "\$this->setSession('contextCode', \$contextCode)") !== false,
    'Successful admission must retain the canonical context session flow.');
$expect(strpos($database, "getObject('usercontext')") !== false,
    'Legacy course membership must remain canonical.');
$expect(strpos($database, "strtolower((string) \$context['access_policy']) === 'private'") !== false
    && strpos($database, "isContextMember(\$this->objUser->userId(), \$context['contextcode'])") !== false,
    'Mapped Private courses must preserve admission for existing canonical members.');
$expect(strpos($database, "strtolower((string) \$context['access_policy']) === 'public'") !== false,
    'Mapped Public courses must admit without optional service infrastructure.');
$expect(strpos($overview, "'admissionAllowed'") !== false
    && strpos($overview, 'Tier 1 access required') !== false,
    'My Learning must not offer an unusable start action for an inaccessible tiered course.');
$expect(strpos($controller, "\$params['error'] = 'accessrequired'") !== false
    && strpos($template, "\$error === 'accessrequired'") !== false,
    'Tier-gate failures must explain the access requirement.');
$expect(strpos($template, 'getAlphaListingAjax') !== false
    && strpos($template, 'if (!$isAccessRequired)') !== false,
    'The legacy alphabetical browser must be hidden for tier-gate failures.');

fwrite(STDOUT, "PASS: tiered course admission compatibility contract\n");
?>
