<?php
/** Seed an empty, isolated design preview with an explicitly public snapshot. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_DESIGN_PREVIEW') !== '1') exit(64);
chdir('/var/www/html/ch');
require 'config/dbdetails_inc.php';
if (!str_ends_with(KEWL_DB_DSN, '@db/chisimba_ltb_preview')) throw new RuntimeException('Wrong database');
$GLOBALS['kewl_entry_point_run'] = true;
$_SERVER += ['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'chisimba.test:8446','SCRIPT_NAME'=>'/ch/index.php','QUERY_STRING'=>''];
require 'classes/core/engine_class_inc.php';
$e = new engine();
$store = $e->getObject('publishingstore','simpleblog');
if ($store->selectPrepared('SELECT id FROM tbl_users LIMIT 1', []) || $store->selectPrepared('SELECT id FROM tbl_simpleblog_posts LIMIT 1', [])) throw new RuntimeException('Preview must be empty');
$mf=$e->getObject('modulefile','modulecatalogue');
$ma=$e->getObject('modulesadmin','modulecatalogue');
foreach (['simpleblog','contentblocks'] as $module) {
    $registration=$mf->readRegisterFile($mf->findRegisterFile($module));
    if (!$ma->installModule($registration,$e->getObject('modules','modulecatalogue')->checkIfRegistered($module))) throw new RuntimeException('Registration failed: '.$module);
}
$config=$e->getObject('dbsysconfig','sysconfig');
function setting($name,$module,$value) { global $config; $config->insertParam($name,$module,$value,'Local design preview'); $config->changeParam($name,$module,$value); }
setting('canvas_preferredcanvas','canvas','learn-the-birds');
setting('TOOLBAR_TYPE','toolbar','site');
setting('COMMUNICATION_TRANSPORT','communications','null');
setting('MAIL_SEND_METHOD','mail','smtp');
setting('MAIL_SMTP_SERVER','mail','127.0.0.1');
setting('MAIL_SMTP_PORT','mail','1');
setting('firstreg_run','modulecatalogue','FALSE');
$provision=$e->getObject('userprovisioningservice','security');
$result=$provision->createLocalUser(['userid'=>'1','username'=>'preview','firstname'=>'Local','surname'=>'Editor','emailaddress'=>'preview@example.invalid','isactive'=>'1'],getenv('PREVIEW_PASSWORD'));
if (empty($result['ok'])) throw new RuntimeException(json_encode($result));
$groups=$e->getObject('groupservice','groupadmin');
$result=$groups->addBootstrapMember($groups->groupIdForName('Site Admin'),'1','Site Admin');
if (empty($result['ok'])) throw new RuntimeException(json_encode($result));
setting('firstreg_run','modulecatalogue','TRUE');
$data=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$authorMap=[];
foreach ($data['authors'] as $i=>$author) {
    $id='preview-author-'.($i+1); $authorMap[$author['userid']]=$id;
    $result=$provision->createLocalUser(['userid'=>$id,'username'=>$id,'firstname'=>$author['firstname'],'surname'=>$author['surname'],'emailaddress'=>$id.'@example.invalid','isactive'=>'0'],bin2hex(random_bytes(24)));
    if (empty($result['ok'])) throw new RuntimeException(json_encode($result));
}
foreach ($data['posts'] as $post) {
    if ($post['post_status']!=='posted' || $post['post_type']!=='site' || !empty($post['required_tier_code'])) throw new RuntimeException('Non-public article');
    $post['userid']=$authorMap[$post['userid']]; $post['modifierid']='1';
    $store->insert($post);
}
$blocks=$e->getObject('dbcontentblocks','contentblocks');
$placement=$e->getObject('preloginblocks','prelogin');
$home=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);
foreach ($home as $block) {
    $saved=$blocks->saveBlock(['scope'=>'site','contextcode'=>'','blocktype'=>'text','blockwidth'=>'wide','title'=>$block['title'],'body_html'=>$block['html'],'show_title'=>'1'],'1');
    $placement->insertBlock(['id'=>md5($saved['id']),'title'=>$block['title'],'side'=>'middle','isblock'=>1,'blockname'=>$saved['id'],'blockmodule'=>'contentblocks']);
}
$placement->insertBlock(['id'=>md5('preview-blog'),'title'=>'News and stories','side'=>'middle','isblock'=>1,'blockname'=>'siteblog','blockmodule'=>'simpleblog']);
echo 'Seeded '.count($data['posts'])." public articles and local editor.\n";
