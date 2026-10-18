<?php
require dirname(__DIR__,2).'/context/tests/native_block_editor_test.php';
$loader=new class {function loadClass(...$args){} function load(){require dirname(__DIR__).'/classes/postloginblockeditor_class_inc.php';}};
$loader->load();
$objects['user']=new class {public bool $logged=true,$persisted=true;function userId(){return 'admin';}function isLoggedIn(){return $this->logged;}function isAdmin(){return true;}function lookupAdmin($id){return $id==='admin'&&$this->persisted;}};
$objects['dynamicblocks']=new class {function getSmallSiteBlocks(){return [['id'=>'site','module'=>'fixture']];}function getWideSiteBlocks(){return [];}function showBlock($id){return 'Site preview';}};
$objects['modules']=new class {function checkIfRegistered($id){return in_array($id,['fixture','context'],true);}};
$objects['dbmoduleblocks']=new class {function getBlocks($width,$type){check($type==='site|postlogin','site catalogue only');return [['blockname'=>'aboutcontext','moduleid'=>'context'],['blockname'=>'old','moduleid'=>'paused']];}};
$editor=new postloginblockeditor();$input['contextcode']='root';$before=$objects['dbcontextblocks']->writes;
check($editor->handle('addblock',$input,'GET')['status']===405,'homepage GET denied');
foreach([null,[], 'expired'] as $token){$bad=$input;$bad['csrf_token']=$token;check($editor->handle('addblock',$bad,'POST')['code']==='block_csrf','homepage token checked');}
$objects['user']->persisted=false;check(!$editor->handle('removeblock',$input,'POST')['ok'],'revoked cached admin denied');$objects['user']->persisted=true;
$objects['user']->logged=false;check(!$editor->handle('removeblock',$input,'POST')['ok'],'anonymous denied');$objects['user']->logged=true;
foreach(['course',[],null] as $scope){$bad=$input;$bad['contextcode']=$scope;check(!$editor->handle('removeblock',$bad,'POST')['ok'],'foreign or malformed scope denied');}
foreach(['block|old|paused','dynamicblock|foreign|fixture','block|unknown|context'] as $id){$bad=$input;$bad['blockid']=$id;check(!$editor->handle('renderblock',$bad,'POST')['ok'],'unoffered preview denied');}
check($objects['dbcontextblocks']->writes===$before,'denials do not write');
check($editor->handle('renderblock',$input,'POST')['ok'],'admin preview');
check($editor->handle('addblock',$input,'POST')['ok'],'admin checked save');
check($editor->purpose('root')!==(new contextblockeditor())->purpose('root'),'token purposes separated');
echo "PASS homepage current administrator, method, CSRF, fixed scope, registered catalogue and shared editor\n";
