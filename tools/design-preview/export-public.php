<?php
/** Read-only export of published public blog articles. No configuration or credentials. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_PUBLIC_PREVIEW_EXPORT') !== '1') exit(64);
chdir('/var/www/html');
$GLOBALS['kewl_entry_point_run'] = true;
$_SERVER += ['REQUEST_METHOD'=>'CLI','HTTP_HOST'=>'learnthebirds.com','SCRIPT_NAME'=>'/index.php','QUERY_STRING'=>''];
ob_start();
require 'classes/core/engine_class_inc.php';
$engine = new engine();
$db = $engine->getObject('publishingstore', 'simpleblog');
$posts = $db->selectPrepared("SELECT id,blogid,userid,datecreated,modifierid,datemodified,post_title,post_content,post_status,post_type,post_tags,author_credit,featured_image,featured_alt,composition_json,legacy_content_html,published_at,required_tier_code FROM tbl_simpleblog_posts WHERE post_type='site' AND post_status='posted' AND COALESCE(required_tier_code,'')=''", []);
$authors = [];
foreach (array_unique(array_column($posts, 'userid')) as $id) {
    $rows=$db->selectPrepared('SELECT userid,firstname,surname FROM tbl_users WHERE userid=?',[$id]);
    if ($rows) $authors[]=$rows[0];
}
ob_end_clean();
echo json_encode(['source'=>'https://learnthebirds.com/','exported_at'=>gmdate('c'),'posts'=>$posts,'authors'=>$authors], JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
