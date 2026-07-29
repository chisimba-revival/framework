<?php
/**
 * Static regression contract for canonical initial-admin provisioning.
 *
 * @category  Chisimba
 * @package   security
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
$root = dirname(__DIR__, 2);
$service = $root
    . '/app/core_modules/security/classes/'
    . 'initialadminprovisioningservice_class_inc.php';
$seed = $root . '/app/core_modules/security/sql/users.xml';
$source = file_get_contents($service);
$manifest = $root . '/app/installer/config/permission-groups.xml';
$groups = simplexml_load_file(
    $manifest,
    'SimpleXMLElement',
    LIBXML_NONET | LIBXML_NOBLANKS
);

function v96Assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

v96Assert(!file_exists($seed), 'seeded tbl_users data must not exist');
v96Assert(
    $groups !== false && $groups->getName() === 'permissionGroups',
    'permission group manifest must load through the runtime XML contract'
);
$requiredGroups = array();
foreach ($groups->group as $group) {
    if (strtolower(trim((string) $group['required'])) === 'true') {
        $requiredGroups[] = trim((string) $group['name']);
    }
}
v96Assert(
    in_array('Site Admin', $requiredGroups, true)
        && in_array('Guest', $requiredGroups, true),
    'Site Admin and Guest must both be required bootstrap groups'
);
v96Assert(
    strpos($source, "getObject(\n            'userprovisioningservice'") !== false,
    'initial admin must depend on UserProvisioningService'
);
v96Assert(
    strpos($source, '->createLocalUser(') !== false,
    'initial admin must be created through canonical provisioning'
);
v96Assert(
    strpos($source, '->findByUserId(') !== false
        && strpos($source, '->findByUsername(') !== false,
    'idempotency must verify both canonical identifiers'
);
v96Assert(
    !preg_match('/\b(?:INSERT|UPDATE|DELETE|SELECT)\b[^;]*tbl_users/i', $source),
    'bootstrap coordinator must not access tbl_users directly'
);
v96Assert(
    !preg_match('/\b(?:sha1|md5|crypt)\s*\(/i', $source),
    'bootstrap coordinator must not perform legacy credential hashing'
);
v96Assert(
    strpos($source, 'password_hash(') === false,
    'bootstrap coordinator must delegate credential transformation'
);

echo "PASS: canonical initial-admin provisioning contract\n";
