<?php
/** Real ACL/menu callers on disposable data under each supported driver. */
require_once $app . '/core_modules/permissions/classes/permissions_acl_class_inc.php';
require_once $app . '/core_modules/toolbar/classes/dbmenu_class_inc.php';
function injectCallerTable(dbTable $caller, $name): void {
    global $db, $table;
    (new ReflectionProperty(dbTable::class,'_db'))->setValue($caller,$db);
    $caller->dbLayer=$table->dbLayer; $caller->dbType='mysqli';
    $caller->_tableName=$name; $caller->_serverName='fixture';
}
$table->executePrepared('CREATE TABLE tbl_permissions_acl (id VARCHAR(64) PRIMARY KEY,acl_id VARCHAR(100),user_id VARCHAR(100),group_id INT) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_groupusers (perm_user_id INT,group_id INT) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_perms_group_subgroups (group_id INT,subgroup_id INT) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_users (id VARCHAR(100),firstname VARCHAR(64),surname VARCHAR(64)) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_groupadmin_group (id INT,name VARCHAR(64)) ENGINE=InnoDB');
$table->executePrepared('INSERT INTO tbl_users VALUES (?,?,?)', ["stored'identity",'Fixture','Person']);
$table->executePrepared('INSERT INTO tbl_groupadmin_group VALUES (?,?)', [10,'Fixture group']);
$table->executePrepared('INSERT INTO tbl_perms_groupusers VALUES (?,?)', [7,10]);
$table->executePrepared('INSERT INTO tbl_perms_group_subgroups VALUES (?,?), (?,?)', [11,10,10,11]);
foreach ([['direct','acl-a',"stored'identity",null],['other-acl','acl-b',"stored'identity",null],['numeric','acl-c','7',null],['child','acl-d',null,10],['parent','acl-e',null,11],['outsider','acl-private','other',99]] as $row) {
    $table->executePrepared('INSERT INTO tbl_permissions_acl VALUES (?,?,?,?)',$row);
}
$acl=new permissions_acl();injectCallerTable($acl,'tbl_permissions_acl');
$acl->objIdentity=new class {public function permissionUserIdForUser($id){return $id==='fixture'?7:null;}};
$acl->objUser=new class {public function PKId($id){return "stored'identity";}};
$grants=$acl->getUserAcls('fixture');sort($grants);
ensureBaseline($grants===['acl-a','acl-b','acl-c','acl-d','acl-e'],'ACL direct/numeric/ancestor scope changed');
ensureBaseline($acl->getUserAcls('missing')===[] && $acl->getUserAcls([])===[],'Unknown identity gained grants');
ensureBaseline($acl->getAclUsers("acl-a' OR 1=1 --")===[],'ACL read injection');
ensureBaseline($acl->getAclUsers('acl-a')[0]['fullName']==='Fixture Person','ACL user join');
ensureBaseline(count($acl->getAclGroups('acl-d'))===1,'ACL group join');
ensureBaseline($acl->deleteAclUser('acl-a',"stored'identity"),'ACL user removal');
ensureBaseline($acl->getAclUsers('acl-a')===[] && count($acl->getAclUsers('acl-b'))===1,'ACL removal affected another grant');
ensureBaseline($acl->deleteAclGroup("acl-d' OR 1=1 --",10),'ACL hostile no-op removal');
ensureBaseline(count($acl->getAclGroups('acl-d'))===1,'Hostile ACL deletion escaped binding');
ensureBaseline($acl->deleteAclGroup('acl-d',10) && $acl->getAclGroups('acl-d')===[],'ACL group removal');
ensureBaseline(in_array('acl-e',$acl->getUserAcls('fixture'),true),'Group removal affected ancestor ACL');
$table->executePrepared('CREATE TABLE tbl_menu_category (id VARCHAR(64) PRIMARY KEY,category VARCHAR(64),module VARCHAR(64),permissions VARCHAR(64),dependscontext INT DEFAULT 0,adminonly INT DEFAULT 0) ENGINE=InnoDB');
$table->executePrepared('CREATE TABLE tbl_modules (module_id VARCHAR(64)) ENGINE=InnoDB');
$table->executePrepared('INSERT INTO tbl_modules VALUES (?)',['fixture']);
$table->executePrepared('INSERT INTO tbl_menu_category (id,category,module) VALUES (?,?,?), (?,?,?)',['link','general','fixture','orphan','general','not-installed']);
$menu=new dbmenu();injectCallerTable($menu,'tbl_menu_category');$menu->table='tbl_menu_category';
ensureBaseline(count($menu->linksForModule('fixture'))===1,'Menu module lookup');
ensureBaseline($menu->getLinkById('link')['module']==='fixture' && $menu->getLinkById('missing')===null,'Menu ID lookup');
ensureBaseline(count($menu->getModules())===1,'Unregistered menu module rendered');
foreach (["link' OR 1=1 --",'../link'] as $invalid) {
    try {$menu->getLinkById($invalid);throw new RuntimeException('Invalid menu ID accepted');}
    catch(InvalidArgumentException $expected){}
}
ensureBaseline($menu->saveLinks(['category'=>'changed'],'link') && $menu->getLinkById('link')['category']==='changed','Menu update');
ensureBaseline($menu->deleteLinkById('link') && $menu->getLinkById('link')===null && $menu->getLinkById('orphan')!==null,'Menu targeted deletion');
foreach (['tbl_permissions_acl','tbl_perms_groupusers','tbl_perms_group_subgroups','tbl_users','tbl_groupadmin_group','tbl_menu_category','tbl_modules'] as $name) $table->executePrepared('DROP TABLE '.$name);
echo "PASS: ACL identity/ancestor isolation, bound removals, menu registration and targeted edits\n";
