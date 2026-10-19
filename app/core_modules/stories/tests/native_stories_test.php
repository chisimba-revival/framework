<?php
/** Test native rendering and public-read policy without enabling a site module. */
class dbTable {
    public function getObject($name,$module=null) {
        return match($name) {
            'dateandtime'=>new class {function hasExpired($date){return false;}},
            'languagecode'=>new class {function getLanguage($code){return $code;}},
            'modules'=>new class {function checkIfRegistered(...$args){return false;}},
            default=>throw new RuntimeException($name)
        };
    }
    public function getParam($name,$default=null){return $default;}
    public function uri($params,$module=null){return 'index.php?'.http_build_query($params);}
}
require __DIR__.'/../classes/sitestories_class_inc.php';
function verify($ok){if(!$ok)throw new RuntimeException('Stories regression');}
$s=new sitestories();
$s->objUser=new class {function isAdmin(){return false;}function lookupAdmin($id){return false;}function userId(){return null;}function isLoggedIn(){return false;}};
$s->objLanguage=new class {function languageText($key,...$args){return $key;}};
$s->objWashout=new class {function parseText($s){return $s;}};
$s->objH=new class {public $type;public $str;function show(){return '<h4>'.$this->str.'</h4>';}};
$s->objDbStories=new class {
 public $rows;
 function __construct(){
  $r=['id'=>'one','category'=>'prelogin','isactive'=>1,'creatorid'=>'fixture','title'=>'<script>title</script>','abstract'=>'Abstract','maintext'=>str_repeat('Birds ',40),'datecreated'=>'2026-10-17','expirationdate'=>'2099-01-01','issticky'=>0,'commentcount'=>0];
  $this->rows=[$r,array_merge($r,['id'=>'two']),array_merge($r,['id'=>'inactive','isactive'=>0]),array_merge($r,['id'=>'private','category'=>'hidden'])];
 }
 function selectPrepared($sql,$params){if(str_contains($sql,'WHERE category')){verify($params===['prelogin','1','en']);return array_slice($this->rows,0,2);}return [];}
 function getRow($key,$id,...$args){foreach($this->rows as $r)if($r['id']===$id)return $r;return false;}
};
$html=$s->fetchPreLoginCategory('prelogin',1,false,'en');
verify(substr_count($html,'<details')===3);
verify(!str_contains($html,'jQuery')&&!str_contains($html,'javascript:'));
verify(str_contains($html,'&lt;script&gt;title&lt;/script&gt;'));
verify($s->readableStory('one',true)!==false);
foreach(['inactive','private','missing'] as $id)verify($s->readableStory($id,true)===false);
echo "PASS native summaries/archives, escaped titles and inactive/private/missing public-read denial\n";
