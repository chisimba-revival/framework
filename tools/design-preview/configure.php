<?php
/** Repeatable local-only navigation and presentation settings. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DESIGN_PREVIEW') !== '1') exit(64);
chdir('/var/www/html/ch');
require 'config/dbdetails_inc.php';
if (!str_ends_with(KEWL_DB_DSN,'@db/chisimba_ltb_preview')) throw new RuntimeException('Wrong database');
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER += ['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'chisimba.test:8446','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>''];
require 'classes/core/engine_class_inc.php';
$e=new engine();
if (!$e->getObject('modules','modulecatalogue')->checkIfRegistered('simpleblog')) {
    $mf=$e->getObject('modulefile','modulecatalogue');
    $reg=$mf->readRegisterFile($mf->findRegisterFile('simpleblog'));
    if (!$e->getObject('modulesadmin','modulecatalogue')->installModule($reg,false)) throw new RuntimeException('Cannot register simpleblog');
}
$menu=$e->getObject('dbmenu','toolbar');
foreach ([['prelogin','Home','house','',10],['simpleblog','News and stories','newspaper','view',20]] as [$module,$label,$icon,$action,$position]) {
    $id='preview-nav-'.$module;
    $menu->saveLinks(['id'=>$id,'module'=>$module,'category'=>'site_'.$position.'||'.$action.'|'.$icon.'|mod_'.$module.'_name|','menu_label'=>$label,'adminonly'=>0,'permissions'=>null,'dependscontext'=>0,'contextplugin'=>0],$menu->getLinkById($id)?$id:null);
}
foreach ($menu->siteLinks() as $row) {
    if ($row['module']==='simpleblog' && $row['id']!=='preview-nav-simpleblog') $menu->deleteLinkById($row['id']);
}
// The public homepage is a snapshot; the separate blog route is fully editable.
$e->getObject('preloginblocks','prelogin')->delete('id',md5('preview-blog'));
$xml=simplexml_load_file('config/config.xml');
$xml->KEWL_POSTLOGIN_MODULE='simpleblog';
$xml->asXML('config/config.xml');
echo "Local navigation configured.\n";
