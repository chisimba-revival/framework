<?php
/**
 * Rollback-only database smoke test for the native ACL resolver.
 *
 * Run in a configured development environment, for example:
 * CHISIMBA_ACL_SMOKE_HOST=database CHISIMBA_ACL_SMOKE_DATABASE=chisimba \
 * CHISIMBA_ACL_SMOKE_USER=root CHISIMBA_ACL_SMOKE_PASSWORD=root \
 * php native_acl_database_smoke_test.php
 */

if (!extension_loaded('mysqli')) {
    fwrite(STDERR, "SKIP: mysqli is unavailable.\n");
    exit(0);
}

$host = getenv('CHISIMBA_ACL_SMOKE_HOST');
if ($host === false || trim($host) === '') {
    fwrite(STDERR, "SKIP: set CHISIMBA_ACL_SMOKE_HOST to run the database smoke test.\n");
    exit(0);
}

$database = getenv('CHISIMBA_ACL_SMOKE_DATABASE') ?: 'chisimba';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(
    $host,
    (string) getenv('CHISIMBA_ACL_SMOKE_USER'),
    (string) getenv('CHISIMBA_ACL_SMOKE_PASSWORD'),
    $database
);
$mysqli->set_charset('utf8mb4');

// Minimal framework seams let this test execute the real getUserAcls method.
class dbTable
{
    public $_tableName = 'tbl_permissions_acl';
    public $objEngine;
    protected $smokeDatabase;

    public function setSmokeDatabase(mysqli $database)
    {
        $this->smokeDatabase = $database;
    }

    public function getArray($sql)
    {
        return $this->smokeDatabase->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}

class SmokeDatabaseAdapter
{
    private $database;
    public function __construct(mysqli $database) { $this->database = $database; }
    public function quoteSmart($value)
    {
        return "'" . $this->database->real_escape_string($value) . "'";
    }
}

class SmokeEngine
{
    private $database;
    public function __construct($database) { $this->database = $database; }
    public function getDbObj() { return $this->database; }
}

class SmokeIdentity
{
    private $logicalUserId;
    private $permissionUserId;
    public function __construct($logicalUserId, $permissionUserId)
    {
        $this->logicalUserId = $logicalUserId;
        $this->permissionUserId = $permissionUserId;
    }
    public function permissionUserIdForUser($userId)
    {
        return (string) $userId === (string) $this->logicalUserId
            ? $this->permissionUserId : null;
    }
}

class SmokeUser
{
    private $logicalUserId;
    private $storageUserId;
    public function __construct($logicalUserId, $storageUserId)
    {
        $this->logicalUserId = $logicalUserId;
        $this->storageUserId = $storageUserId;
    }
    public function PKId($userId)
    {
        return (string) $userId === (string) $this->logicalUserId
            ? $this->storageUserId : null;
    }
}

$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__) . '/classes/permissions_acl_class_inc.php';

function assertAclSet($label, $actual, $expected)
{
    sort($actual);
    sort($expected);
    if ($actual !== $expected) {
        throw new RuntimeException(
            $label . ' expected [' . implode(',', $expected) . '] but got ['
            . implode(',', $actual) . ']'
        );
    }
}

$checks = 0;
$mysqli->begin_transaction();
try {
    $user = $mysqli->query(
        'SELECT u.userId, u.id, pu.perm_user_id'
        . ' FROM tbl_users u INNER JOIN tbl_perms_perm_users pu'
        . ' ON CAST(pu.auth_user_id AS CHAR) = CAST(u.userId AS CHAR)'
        . ' ORDER BY pu.perm_user_id LIMIT 1'
    )->fetch_assoc();
    if (!$user) {
        throw new RuntimeException('No canonical user is available for ACL smoke testing.');
    }

    $suffix = substr(hash('sha256', uniqid('acl', true)), 0, 12);
    $parentGroup = 900000000 + random_int(1000, 9999);
    $childGroup = $parentGroup + 1;
    $otherGroup = $parentGroup + 2;
    $ids = array(
        'parent' => 'atp' . $suffix,
        'child' => 'atc' . $suffix,
        'other' => 'ato' . $suffix,
        'edge' => 'ate' . $suffix,
        'member' => 'atm' . $suffix,
        'directAcl' => 'atd' . $suffix,
        'groupAcl' => 'atg' . $suffix,
        'parentAcl' => 'ati' . $suffix,
        'otherAcl' => 'atu' . $suffix,
    );

    $insertGroup = $mysqli->prepare(
        'INSERT INTO tbl_perms_groups (id, group_id, group_type, group_define_name)'
        . ' VALUES (?, ?, 0, ?)'
    );
    $insertGroup->bind_param('sis', $groupRowId, $groupNumber, $groupName);
    foreach (array(
        array($ids['parent'], $parentGroup, 'ACL smoke parent'),
        array($ids['child'], $childGroup, 'ACL smoke child'),
        array($ids['other'], $otherGroup, 'ACL smoke unrelated'),
    ) as $group) {
        list($groupRowId, $groupNumber, $groupName) = $group;
        $insertGroup->execute();
    }
    $edge = $mysqli->prepare(
        'INSERT INTO tbl_perms_group_subgroups (id, group_id, subgroup_id)'
        . ' VALUES (?, ?, ?)'
    );
    $edgeId = $ids['edge'];
    $edge->bind_param('sii', $edgeId, $parentGroup, $childGroup);
    $edge->execute();
    $membership = $mysqli->prepare(
        'INSERT INTO tbl_perms_groupusers (id, group_id, perm_user_id) VALUES (?, ?, ?)'
    );
    $permissionUserId = (int) $user['perm_user_id'];
    $membershipId = $ids['member'];
    $membership->bind_param('sii', $membershipId, $childGroup, $permissionUserId);
    $membership->execute();

    $insertDescription = $mysqli->prepare(
        'INSERT INTO tbl_permissions_acl_description'
        . ' (id, name, description, last_updated, last_updated_by)'
        . ' VALUES (?, ?, ?, NOW(), ?)'
    );
    $description = 'Rollback-only ACL smoke capability';
    $updatedBy = (string) $user['userId'];
    $insertDescription->bind_param('ssss', $aclId, $aclName, $description, $updatedBy);
    foreach (array('directAcl', 'groupAcl', 'parentAcl', 'otherAcl') as $key) {
        $aclId = $ids[$key];
        $aclName = 'acl.smoke.' . $key . '.' . $suffix;
        $insertDescription->execute();
    }

    $insertGrant = $mysqli->prepare(
        'INSERT INTO tbl_permissions_acl'
        . ' (id, acl_id, user_id, group_id, last_updated, last_updated_by)'
        . ' VALUES (?, ?, ?, ?, NOW(), ?)'
    );
    $insertGrant->bind_param('sssis', $grantId, $grantAclId, $grantUserId, $grantGroupId, $updatedBy);
    foreach (array(
        array('agd' . $suffix, $ids['directAcl'], $user['id'], null),
        array('agg' . $suffix, $ids['groupAcl'], null, $childGroup),
        array('agi' . $suffix, $ids['parentAcl'], null, $parentGroup),
        array('agu' . $suffix, $ids['otherAcl'], null, $otherGroup),
        // A duplicate effective grant must not duplicate the returned capability.
        array('agx' . $suffix, $ids['parentAcl'], null, $childGroup),
    ) as $grant) {
        list($grantId, $grantAclId, $grantUserId, $grantGroupId) = $grant;
        $insertGrant->execute();
    }

    $resolver = new permissions_acl();
    $resolver->setSmokeDatabase($mysqli);
    $resolver->objEngine = new SmokeEngine(new SmokeDatabaseAdapter($mysqli));
    $resolver->objIdentity = new SmokeIdentity($user['userId'], $user['perm_user_id']);
    $resolver->objUser = new SmokeUser($user['userId'], $user['id']);

    assertAclSet('direct, group and inherited grants', $resolver->getUserAcls($user['userId']), array(
        $ids['directAcl'], $ids['groupAcl'], $ids['parentAcl'],
    ));
    $checks++;

    assertAclSet('unknown identity fails closed', $resolver->getUserAcls('missing-user'), array());
    $checks++;

    $deleteMembership = $mysqli->prepare('DELETE FROM tbl_perms_groupusers WHERE id = ?');
    $deleteMembership->bind_param('s', $membershipId);
    $deleteMembership->execute();
    assertAclSet('removed membership removes group grants', $resolver->getUserAcls($user['userId']), array(
        $ids['directAcl'],
    ));
    $checks++;

    $directGrantId = 'agd' . $suffix;
    $deleteGrant = $mysqli->prepare('DELETE FROM tbl_permissions_acl WHERE id = ?');
    $deleteGrant->bind_param('s', $directGrantId);
    $deleteGrant->execute();
    assertAclSet('removed direct grant denies capability', $resolver->getUserAcls($user['userId']), array());
    $checks++;

} finally {
    $mysqli->rollback();
}

$cleanup = $mysqli->prepare(
    'SELECT COUNT(*) AS cnt FROM tbl_permissions_acl WHERE acl_id IN (?, ?, ?, ?)'
);
$cleanup->bind_param(
    'ssss',
    $ids['directAcl'], $ids['groupAcl'], $ids['parentAcl'], $ids['otherAcl']
);
$cleanup->execute();
$remaining = $cleanup->get_result()->fetch_assoc();
if ((int) $remaining['cnt'] !== 0) {
    throw new RuntimeException('ACL smoke fixtures remained after rollback.');
}
$checks++;
echo 'PASS: ' . $checks . " rollback-only database ACL smoke scenarios.\n";
