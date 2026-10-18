<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(static function($n,$s){throw new ErrorException($s,0,$n);});
class ChisimbaObject {function getObject($name,$module=''){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/contextsettingsaccess_class_inc.php';
function check($ok,$why){if(!$ok)throw new RuntimeException($why);}
$objects['user']=new class {public bool $logged=true,$admin=true;function userId(){return 'actor';}function isLoggedIn(){return $this->logged;}function isAdmin(){return true;}function lookupAdmin($id){return $this->admin;}};
$objects['contextauthorservice']=new class {public bool $author=false;function isAuthor($code,$id){return $this->author && $code==='course';}};
$csrf=new class {function consume($purpose,$token){return $purpose==='context_settings_'.substr(hash('sha256','course'),0,32) && $token==='valid';}};
$objects['nativeauthwebcomposition']=new class($csrf) {function __construct(private $csrf){}function build(){return ['csrf'=>$this->csrf];}};
$guard=new contextsettingsaccess();$input=['contextcode'=>'course','csrf_token'=>'valid'];
check($guard->check('course',$input,'POST')==='','current admin');
check($guard->check('course',$input,'GET')==='method','GET denied');
foreach(['',null,[],'expired'] as $token){$bad=$input;$bad['csrf_token']=$token;check($guard->check('course',$bad,'POST')==='csrf','invalid token denied');}
foreach(['other',null,[],'root'] as $scope){$bad=$input;$bad['contextcode']=$scope;check($guard->check('course',$bad,'POST')==='denied','scope denied');}
$objects['user']->logged=false;check(!$guard->canEdit('course'),'anonymous');$objects['user']->logged=true;
$objects['user']->admin=false;check(!$guard->canEdit('course'),'cached revoked admin or ordinary member');
$objects['contextauthorservice']->author=true;check($guard->canEdit('course'),'persisted course author');check(!$guard->canEdit('foreign'),'foreign course');
$objects['contextauthorservice']->author=false;check(!$guard->canEdit('course'),'revoked author');
foreach(['root','',null,[]] as $code)check(!$guard->canEdit($code),'invalid scope');
echo "PASS settings current admin/author, revoked roles, POST, CSRF and fixed scope\n";
