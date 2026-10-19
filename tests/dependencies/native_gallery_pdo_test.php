<?php
/** Run from the local installation root; synthetic records always roll back. */
if (PHP_SAPI !== 'cli' || getenv('CHISIMBA_NATIVE_PREVIEW_TEST') !== '1') exit("Set CHISIMBA_NATIVE_PREVIEW_TEST=1 locally.\n");
$GLOBALS['kewl_entry_point_run']=true;
$_SERVER['REQUEST_METHOD']='CLI'; $_SERVER['HTTP_HOST']='chisimba.test:8445';
$_SERVER['SCRIPT_NAME']='/ch/index.php'; $_SERVER['QUERY_STRING']='';
require 'classes/core/engine_class_inc.php';
$engine=new engine();
set_error_handler(function($n,$s){if(error_reporting()&$n)throw new RuntimeException($s);return false;});
$files=$engine->getObject('dbfile','filemanager');
$folders=$engine->getObject('dbfolder','filemanager');
$user=$engine->getObject('user','security');
$uid=$user->getUserId('admin');
if (!$uid || $user->isLoggedIn()) throw new RuntimeException('Requires local admin identity and anonymous CLI session');
$folder='users/'.$uid.'/native-gallery-qa';
$gallery=$engine->getObject('parse4pictureshow','filters');
$before=count($files->selectPrepared('SELECT id FROM tbl_files WHERE filefolder=?',[$folder]));
if ($before) throw new RuntimeException('Fixture collision');
try {
    $files->transaction(function()use($files,$folders,$uid,$folder,$gallery){
        $folders->insert(['folderpath'=>$folder,'folderlevel'=>3,'access'=>'public']);
        foreach (['public','hidden'] as $visibility) {
            $files->insert(['userid'=>$uid,'creatorid'=>$uid,'modifierid'=>$uid,
                'filename'=>$visibility.'.png','datatype'=>'png','path'=>$folder.'/'.$visibility.'.png',
                'filefolder'=>$folder,'mimetype'=>'image/png','filesize'=>1,'category'=>'images',
                'access'=>'public','visibility'=>$visibility==='hidden'?'hidden':'visible']);
        }
        $html=$gallery->parse('[PICTURESHOW:username=admin,folder=native-gallery-qa]');
        if (!str_contains($html,'public.png') || str_contains($html,'hidden.png') || str_contains($html,'usrfiles/')) {
            throw new RuntimeException('Real gallery query/policy failed');
        }
        throw new LogicException('rollback-fixtures');
    });
} catch (LogicException $done) { if ($done->getMessage()!=='rollback-fixtures') throw $done; }
if ($files->selectPrepared('SELECT id FROM tbl_files WHERE filefolder=?',[$folder])
    || $folders->getFolderByPath($folder)) throw new RuntimeException('Fixture rollback failed');
echo "PASS real PDO gallery selection, anonymous public/hidden policy and rollback\n";
