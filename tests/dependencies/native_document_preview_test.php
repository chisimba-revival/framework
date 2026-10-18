<?php
/** Behavioural checks for native previews and the PICTURESHOW permission boundary. */
set_error_handler(function($n,$s){ throw new RuntimeException($s); });
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject {
    public array $services = [];
    public function getObject($name, $module = null) { return $this->services[$name]; }
    public function uri($params, $module = null) { return '/index.php?' . http_build_query(['module'=>$module] + $params); }
}
require dirname(__DIR__,2).'/app/core_modules/filemanager/classes/filepreview_class_inc.php';
require dirname(__DIR__,2).'/app/core_modules/filters/classes/parse4pictureshow_class_inc.php';
function verify($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$preview = new filepreview();
$preview->objConfig = new class { function getcontentPath(){return '/unused/';} function getcontentBasePath(){return '/unused/';} };
$preview->services['language'] = new class { function languageText($key){return 'Download';} };
foreach (['pdf','ppt','pptx','doc','docx','odt','xlsx'] as $type) {
    $preview->file = ['datatype'=>$type,'filename'=>'<unsafe>.'.$type,'linkname'=>'/index.php?module=filemanager&action=file&id=f'];
    $html=$preview->showDocument();
    verify(str_contains($html,'action=file&amp;id=f'), 'Canonical delivery link missing');
    verify(str_contains($html,'&lt;unsafe&gt;'), 'Filename not escaped');
    verify(!str_contains($html,'.swf'), 'Flash preview returned');
    verify(str_contains($html,'<iframe') === ($type==='pdf'), 'Only PDF receives inline preview');
}
$preview->objFiles = new class { function getFileInfo($id){return ['id'=>$id];} };
$preview->services['filereadpolicy'] = new class { function mayRead($file){return false;} };
verify($preview->previewFile('denied')==='', 'Denied preview leaked content');
$gallery = new parse4pictureshow();
$gallery->services['user'] = new class { function getUserId($name){return $name==='missing' ? false : 'u1';} };
$db = new class {
    public array $queries=[];
    function selectPrepared($sql,$params){
        $this->queries[]=$params;
        return [
            ['id'=>'allowed','datatype'=>'png','filename'=>'<bird>.png'],
            ['id'=>'denied','datatype'=>'jpg','filename'=>'secret.jpg'],
            ['id'=>'active','datatype'=>'svg','filename'=>'active.svg']
        ];
    }
};
$gallery->services['dbfile']=$db;
$gallery->services['filereadpolicy']=new class {function mayRead($file){return $file['id']!=='denied';}};
$html=$gallery->parse('[PICTURESHOW:username=admin,folder=pictures]');
verify(str_contains($html,'id=allowed') && str_contains($html,'&lt;bird&gt;'), 'Authorised image/escaping missing');
verify(!str_contains($html,'secret') && !str_contains($html,'active.svg'), 'Gallery leaked denied/active content');
verify($db->queries[0]===['users/u1/pictures','images'], 'Folder query not bound correctly');
foreach(['../secret','a/../secret','a\\secret'] as $folder) verify($gallery->parse('[PICTURESHOW:folder='.$folder.']')==='', 'Traversal accepted');
verify($gallery->parse('[PICTURESHOW:username=missing]')==='', 'Unknown user accepted');
verify($gallery->parse('Unchanged text')==='Unchanged text','Ordinary content changed');
foreach(json_decode(file_get_contents(__DIR__.'/retired-document-plugin-paths.json'),true) as $path) {
    verify(!file_exists(dirname(__DIR__,2).'/'.$path), 'Retired asset returned: '.$path);
}
echo "PASS native document links, PDF preview, denied access, gallery permissions, escaping and traversal\n";
