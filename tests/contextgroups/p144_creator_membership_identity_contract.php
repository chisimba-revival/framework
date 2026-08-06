<?php
/**
 * Contract for canonical Context creator membership identity conversion.
 *
 * @author Derek Keats
 */
$source = file_get_contents(
    dirname(__DIR__, 2)
    . '/app/core_modules/contextgroups/classes/managegroups_class_inc.php'
);
if ($source === false) {
    fwrite(STDERR, "FAIL: unable to read managegroups source\n");
    exit(1);
}
$required = array(
    "'identityservice',\n            'security'",
    '->permissionUserIdForUser($userId)',
    'Canonical Context member identity could not be resolved',
    '$permissionUserId',
    'Canonical Context group membership could not be created',
);
foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: missing canonical creator-membership contract\n");
        exit(1);
    }
}
if (strpos(
    $source,
    '$this->_objGroupAdmin->addGroupUser( $row[\'id\'], $userPKId )'
) !== false) {
    fwrite(STDERR, "FAIL: legacy Context membership write remains\n");
    exit(1);
}
echo "PASS: Context members resolve through IdentityService before storage.\n";
