<?php
/**
 * Focused behavioural test for the canonical PermissionService.
 *
 * @author Derek Keats
 */
class dbTable
{
    protected $_tableName = '';

    public function init($tableName = null, $pearDb = null, $errorCallback = null)
    {
        $this->_tableName = $tableName;
    }

    public function getObject($name, $module)
    {
        return null;
    }

    public function insert($data)
    {
        return true;
    }
}

require_once dirname(__FILE__) . '/../../app/core_modules/security/classes/'
    . 'permissionservice_class_inc.php';

class permissionservicefixture extends permissionservice
{
    public $queries = array();
    public $responses = array();
    public $responseQueues = array();
    public $inserted = array();
    public $permissionUserId = 7;

    public function getArray($sql)
    {
        $this->queries[] = $sql;
        foreach ($this->responseQueues as $needle => &$queue) {
            if (strpos($sql, $needle) !== false && count($queue) > 0) {
                return array_shift($queue);
            }
        }
        unset($queue);
        foreach ($this->responses as $needle => $response) {
            if (strpos($sql, $needle) !== false) {
                return $response;
            }
        }
        return array();
    }

    public function insert($data)
    {
        $this->inserted[] = array($this->_tableName, $data);
        return true;
    }

    protected function permissionUserIdForUser($userId)
    {
        return $this->permissionUserId;
    }
}

function permissionAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

$direct = new permissionservicefixture();
$direct->responses = array(
    'FROM tbl_perms_rights WHERE right_id = 4' => array(array('right_id' => 4)),
    'FROM tbl_perms_userrights' => array(array('id' => 'direct')),
);
permissionAssert($direct->isGranted('1', 4), 'direct user grant was denied');

$group = new permissionservicefixture();
$group->responses = array(
    'FROM tbl_perms_rights WHERE right_id = 5' => array(array('right_id' => 5)),
    'FROM tbl_perms_userrights' => array(),
    'FROM tbl_perms_grouprights AS gr' => array(array('id' => 'group')),
);
permissionAssert($group->isGranted('1', 5), 'canonical group grant was denied');

$denied = new permissionservicefixture();
$denied->responses = array(
    'FROM tbl_perms_rights WHERE right_id = 6' => array(array('right_id' => 6)),
);
permissionAssert(!$denied->isGranted('1', 6), 'missing grant did not fail closed');
permissionAssert(!$denied->isGranted('1', 'not-an-id'), 'malformed right was accepted');

$unknown = new permissionservicefixture();
$unknown->responses = array(
    'FROM tbl_perms_rights WHERE right_id = 99' => array(),
);
permissionAssert(!$unknown->isGranted('1', 99), 'unknown right was accepted');

$area = new permissionservicefixture();
$area->responses = array(
    'WHERE area_id = 3' => array(
        array('right_id' => 8, 'right_define_name' => 'access'),
    ),
);
permissionAssert(
    $area->rightIdForArea(3, 'access') === 8,
    'area-scoped right did not resolve'
);

$duplicate = new permissionservicefixture();
$duplicate->responses = array(
    'WHERE area_id = 3' => array(
        array('right_id' => 8, 'right_define_name' => 'access'),
        array('right_id' => 9, 'right_define_name' => 'access'),
    ),
);
permissionAssert(
    $duplicate->rightIdForArea(3, 'access') === null,
    'duplicate area right did not fail closed'
);

$grant = new permissionservicefixture();
$grant->responses = array(
    'FROM tbl_perms_groups WHERE group_id = 2' => array(array('group_id' => 2)),
    'FROM tbl_perms_rights WHERE right_id = 4' => array(array('right_id' => 4)),
    'FROM tbl_perms_grouprights WHERE group_id = 2' => array(),
);
permissionAssert($grant->ensureGroupGrant(2, 4), 'group grant insert failed');
permissionAssert(count($grant->inserted) === 1, 'group grant was not inserted once');
permissionAssert(
    $grant->inserted[0][0] === 'tbl_perms_grouprights',
    'group grant used the wrong owned table'
);

$existingArea = new permissionservicefixture();
$existingArea->responses = array(
    'FROM tbl_perms_areas WHERE application_id' => array(
        array('area_id' => 12),
    ),
);
permissionAssert(
    $existingArea->ensureArea('chisimba', 'toolbar') === 12,
    'existing canonical area was not resolved'
);
permissionAssert(
    count($existingArea->inserted) === 0,
    'existing canonical area was inserted again'
);

$newArea = new permissionservicefixture();
$newArea->responseQueues = array(
    'FROM tbl_perms_areas WHERE application_id' => array(
        array(),
        array(array('area_id' => 13)),
    ),
);
$newArea->responses = array(
    'SELECT MAX(area_id) AS maximum_id FROM tbl_perms_areas' => array(
        array('maximum_id' => 12),
    ),
);
permissionAssert(
    $newArea->ensureArea('chisimba', 'toolbar') === 13,
    'new canonical area was not created'
);
permissionAssert(
    count($newArea->inserted) === 1
        && $newArea->inserted[0][0] === 'tbl_perms_areas',
    'canonical area used the wrong owned table'
);

$newRight = new permissionservicefixture();
$newRight->responseQueues = array(
    'FROM tbl_perms_rights WHERE area_id = 13' => array(
        array(),
        array(array(
            'right_id' => 22,
            'right_define_name' => 'view',
        )),
    ),
);
$newRight->responses = array(
    'FROM tbl_perms_areas WHERE area_id = 13' => array(
        array('area_id' => 13),
    ),
    'SELECT MAX(right_id) AS maximum_id FROM tbl_perms_rights' => array(
        array('maximum_id' => 21),
    ),
    'FROM tbl_perms_groups WHERE group_id = 2' => array(
        array('group_id' => 2),
    ),
    'FROM tbl_perms_rights WHERE right_id = 22' => array(
        array('right_id' => 22),
    ),
    'FROM tbl_perms_grouprights WHERE group_id = 2' => array(),
);
permissionAssert(
    $newRight->ensureRight(13, 'view', 2) === 22,
    'new canonical right was not defined and granted'
);
permissionAssert(
    $newRight->inserted[0][0] === 'tbl_perms_rights'
        && $newRight->inserted[1][0] === 'tbl_perms_grouprights',
    'right definition or administrator grant used the wrong owned table'
);

$listed = new permissionservicefixture();
$listed->responses = array(
    'FROM tbl_perms_areas WHERE area_id = 13' => array(
        array('area_id' => 13),
    ),
    'FROM tbl_perms_rights WHERE area_id = 13' => array(
        array(
            'right_id' => 22,
            'right_define_name' => 'view',
            'has_implied' => '',
        ),
    ),
);
$rights = $listed->rightsForArea(13);
permissionAssert(
    count($rights) === 1
        && $rights[0]['rightId'] === 22
        && $rights[0]['name'] === 'view',
    'canonical rights were not enumerated'
);

$invalidDefinition = new permissionservicefixture();
permissionAssert(
    $invalidDefinition->ensureArea('', 'toolbar') === null,
    'malformed application identifier was accepted'
);
permissionAssert(
    $invalidDefinition->ensureRight(1, "bad\nright") === null,
    'malformed right definition was accepted'
);

echo "PASS: canonical permission definition service\n";
?>
