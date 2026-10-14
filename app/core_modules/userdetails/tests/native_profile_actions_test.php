<?php
$GLOBALS['kewl_entry_point_run']=true;
set_error_handler(function($n,$s){throw new RuntimeException($s);});
class ChisimbaObject {
    public array $services=[];
    function getObject($name,$module=null){if(!isset($this->services[$name]))throw new LogicException('Unexpected service '.$name);return $this->services[$name];}
}
require dirname(__DIR__).'/classes/profileactions_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$user=new class {public bool $logged=true,$admin=false;function isLoggedIn(){return $this->logged;}function isAdmin(){return $this->admin;}function userId(){return 'self';}function getUserImage(...$args){return '<img src="self.png">';}};
$csrf=new class {function consume($context,$token){return $token==='valid';}function issue($context){return 'renewed';}};
$composition=new class($csrf) {function __construct(private $csrf){} function build(){return ['csrf'=>$this->csrf];}};
$photo=new class {public array $calls=[];function change($id){$this->calls[]=$id;if($id==='bad')throw new RuntimeException('profile_image_access');}function reset(){$this->calls[]='reset';}};
$modules=new class {public bool $registered=false;function checkIfRegistered($name){return $this->registered;}};
$actions=new profileactions();$actions->services=['user'=>$user,'nativeauthwebcomposition'=>$composition,'profilephoto'=>$photo,'modules'=>$modules,'language'=>new class {function languageText($key,...$args){return $key;}}];
foreach(['GET','PUT'] as $method)check($actions->handle('ajaxChangeImage',['csrf_token'=>'valid','imageselect'=>'own'],$method)['status']===405,'Reject non-POST');
foreach([null,[], 'expired'] as $token) {
 $r=$actions->handle('ajaxChangeImage',['csrf_token'=>$token,'imageselect'=>'own'],'POST');check(!$r['ok']&&$r['status']===403&&$r['csrf_token']==='renewed','CSRF denial and recovery');
}
check($photo->calls===[],'Denied requests did not write');
$user->logged=false;check($actions->handle('ajaxResetImage',['csrf_token'=>'valid'],'POST')['status']===403,'Anonymous denied');$user->logged=true;
check($actions->handle('ajaxChangeImage',['csrf_token'=>'valid','imageselect'=>'bad'],'POST')['code']==='profile_image_access','Photo policy failure retained');
check($actions->handle('ajaxChangeImage',['csrf_token'=>'valid','imageselect'=>'own','userid'=>'victim'],'POST')['ok'],'Own photo action');
check(end($photo->calls)==='own','Submitted user ID ignored');
check($actions->handle('ajaxResetImage',['csrf_token'=>'valid'],'POST')['ok'],'Reset uses guarded action');
check($actions->handle('ajaxChangeGrade',['csrf_token'=>'valid','new_name'=>'Site Admins'],'POST')['status']===403,'Non-admin grade change denied');
check($actions->schools('School')===[],'Missing school module fails closed');
$modules->registered=true;
$actions->services['dbschools_schools']=new class {public array $terms=[];function autocomplete($term){$this->terms[]=$term;return [['label'=>'<School>','value'=>'school-id']];}};
check($actions->schools('ab')===[],'Minimum lookup length');check($actions->schools('School')===[['label'=>'<School>','value'=>'school-id']],'Optional school data contract');
$user->admin=true;
$actions->services['dbgrades']=new class {function getAll(){return [['name'=>'Grade 1'],['name'=>'Grade 2']];}function transaction($callback){$callback();}};
$groups=new class {public array $calls=[];function groupIdForName($name){return $name;}function addMember($group,$id){$this->calls[]=['add',$group,$id];return ['ok'=>true];}function getMembers($id){return [['userId'=>'self'],['userId'=>'other']];}function removeMember($group,$id){$this->calls[]=['remove',$group,$id];return ['ok'=>true];}};
$actions->services['groupservice']=$groups;
check(!$actions->handle('ajaxChangeGrade',['csrf_token'=>'valid','new_name'=>'Site Admins'],'POST')['ok'],'Reject arbitrary group');
check($groups->calls===[],'Invalid group causes no writes');
check($actions->handle('ajaxChangeGrade',['csrf_token'=>'valid','new_name'=>'Grade 2','old_name'=>'Site Admins'],'POST')['ok'],'Allowed grade change');
check($groups->calls===[['add','Grade 2','self'],['remove','Grade 1','self']],'Only persisted grade memberships for self changed');
echo "PASS methods, CSRF recovery, identity, photo actions, optional schools and restricted grade writes\n";
