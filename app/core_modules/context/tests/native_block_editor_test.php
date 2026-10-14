<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {public static array $objects=[];function getObject($name,$module=''){return self::$objects[$name];}}
require dirname(__DIR__).'/classes/contextblockeditor_class_inc.php';
$objects=&ChisimbaObject::$objects;
$objects['dbcontext']=new class{function getContextCode(){return 'course';}};
$objects['user']=new class{public bool $logged=true,$admin=true;function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}};
$objects['managegroups']=new class{public bool $lecturer=false;function isContextLecturer(){return $this->lecturer;}};
$csrf=new class{function consume($purpose,$token){return $token==='valid';}function issue($purpose){return 'renewed';}};
$objects['nativeauthwebcomposition']=new class($csrf){function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}};
$objects['language']=new class{function languageText($key,$module){return $key;}};
$objects['dbcontextblocks']=new class{public int $writes=0;function editPlacement(...$args){++$this->writes;return 'new-revision';}};
$objects['dynamicblocks']=new class{function getSmallContextBlocks($code){return [['id'=>'own','module'=>'fixture']];}function getWideContextBlocks($code){return [];}function showBlock($id){return 'Scoped dynamic block';}};
$objects['dbmoduleblocks']=new class{function getBlocks($width,$type){return [['blockname'=>'aboutcontext','moduleid'=>'context']];}};
$objects['modules']=new class{function checkIfRegistered($module){return false;}};
$objects['blocks']=new class{function showBlock(...$args){return 'Allowed block';}};
$editor=new contextblockeditor();$input=['contextcode'=>'course','csrf_token'=>'valid','revision'=>'original','blockid'=>'block|aboutcontext|context','side'=>'middle','direction'=>'up'];
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
check($editor->handle('addblock',$input,'GET')['status']===405,'GET rejected');
foreach([null,[], 'expired'] as $token){$bad=$input;$bad['csrf_token']=$token;check($editor->handle('addblock',$bad,'POST')['code']==='block_csrf','CSRF rejected');}
$bad=$input;$bad['contextcode']='other';check($editor->handle('removeblock',$bad,'POST')['status']===403,'session scope checked');
$objects['user']->logged=false;check(!$editor->handle('addblock',$input,'POST')['ok'],'anonymous denied');$objects['user']->logged=true;
$objects['user']->admin=false;check(!$editor->handle('addblock',$input,'POST')['ok'],'student or outsider denied');
$objects['managegroups']->lecturer=true;check($editor->handle('renderblock',$input,'POST')['ok'],'instructor preview');
$objects['managegroups']->lecturer=false;check(!$editor->handle('moveblock',$input,'POST')['ok'],'revoked instructor denied');$objects['user']->admin=true;
foreach(['block|arbitrary|security','dynamicblock|foreign|fixture'] as $id){$bad=$input;$bad['blockid']=$id;check(!$editor->handle('renderblock',$bad,'POST')['ok'],'unoffered block denied');}
$bad=$input;$bad['side']='bad';check(!$editor->handle('addblock',$bad,'POST')['ok'],'side denied');
$bad=$input;$bad['direction']='arbitrary';check(!$editor->handle('moveblock',$bad,'POST')['ok'],'direction denied');
check($objects['dbcontextblocks']->writes===0,'negative paths never persist');
check($editor->handle('addblock',$input,'POST')['revision']==='new-revision'&&$objects['dbcontextblocks']->writes===1,'valid add delegates once');
echo "PASS methods, CSRF, current scope, anonymous/student/revoked roles, block allowlist and valid save\n";
