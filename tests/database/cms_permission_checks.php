<?php
/** Regression for CMS permission mapping identity, using real disposable PDO. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DB_BASELINE') !== '1') exit(64);
require_once '/modules/cmsadmin/classes/dbuserpermissions_class_inc.php';
$table->executePrepared('CREATE TEMPORARY TABLE tbl_cms_user_permissions (id VARCHAR(32) PRIMARY KEY,user_id VARCHAR(32),show_on_frontpage BOOLEAN) ENGINE=InnoDB');
try {
 $repo=new dbuserpermissions(); injectCallerTable($repo,'tbl_cms_user_permissions');
 $table->executePrepared('INSERT INTO tbl_cms_user_permissions VALUES (?,?,?),(?,?,?)',['record-one','user-one',0,'record-two','user-two',0]);
 $repo->editUserPermission('record-one','user-three',true);
 $one=$repo->getRecord('record-one');$two=$repo->getRecord('record-two');
 ensureBaseline($one['user_id']==='user-three' && (int)$one['show_on_frontpage']===1,'CMS edit uses mapping ID, chosen user and boolean in correct positions');
 ensureBaseline($two['user_id']==='user-two' && (int)$two['show_on_frontpage']===0,'CMS edit preserves other mapping');
 $repo->editUserPermission("' OR 1=1 --",'bad',true);
 ensureBaseline($repo->getRecord('record-two')===$two,'CMS mapping ID remains bound');
 $repo->deleteRecord('record-one');
 ensureBaseline(!$repo->getRecord('record-one') && $repo->getRecord('record-two')===$two,'CMS deletion is scoped to mapping ID');
 echo "PASS CMS permission mapping: correct edit arguments, bound record identity and scoped deletion\n";
} finally {$table->executePrepared('DROP TEMPORARY TABLE IF EXISTS tbl_cms_user_permissions');}
