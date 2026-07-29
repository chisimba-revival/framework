<?php
$source = file_get_contents(
    dirname(__FILE__) . '/../../app/core_modules/toolbar/classes/'
    . 'toolbarsecuritycontext_class_inc.php'
);
$required = array(
    "nativeauthwebcomposition",
    "userservice",
    "groupservice",
    "permissionservice",
    "isAuthenticated()",
    "getUserId()",
    "findByUserId",
    "groupIdForName('Site Admin')",
    "isGroupMember",
    "isGranted",
    "native_auth_logout",
    'method="post"',
);
foreach ($required as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "Missing canonical context contract: $needle\n");
        exit(1);
    }
}
$forbidden = array(
    "getObject('user', 'security')",
    'LiveUser',
    "getObject('perms', 'permissions')",
    "getObject('permissions_model', 'permissions')",
    "getObject('groupAdminModel', 'groupadmin')",
    "action' => 'logoff",
    'javascript:',
);
foreach ($forbidden as $needle) {
    if (strpos($source, $needle) !== false) {
        fwrite(STDERR, "Legacy toolbar security dependency: $needle\n");
        exit(1);
    }
}
echo "Toolbar canonical security context contract passed.\n";
