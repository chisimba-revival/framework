<?php
require __DIR__.'/native_block_editor_test.php';
$loader = new class { function loadClass(...$args) {} function load() { require dirname(__DIR__).'/classes/dashboardblockeditor_class_inc.php'; } };
$loader->load();
class FixtureDashboardEditor extends dashboardblockeditor {
    public function newObject($name,$module='') {
        if ($name === 'block_broken') throw new RuntimeException('Optional unavailable block');
        return new class($name) {
            public string $title = 'Fixture';
            public function __construct(private string $name) {}
            public function isAvailableForBlockChooser() { return $this->name !== 'block_hidden'; }
        };
    }
}
$objects['user'] = new class { public bool $logged=true,$persisted=true; function userId(){return 'admin';} function isLoggedIn(){return $this->logged;} function isAdmin(){return true;} function lookupAdmin($id){return $id==='admin' && $this->persisted;} };
$objects['modules'] = new class { function checkIfRegistered($id){return $id !== 'paused';} };
$objects['dynamicblocks'] = new class {function getSmallSiteBlocks(){return [['id'=>'site','module'=>'fixture','title'=>'Site']];}function getWideSiteBlocks(){return [];}function showBlock($id){return 'Site preview';}};
$objects['dbmoduleblocks'] = new class {
    public string $role='';
    function getBlocks($width,$type,$role) {
        check($type === 'site|user|postlogin', 'site and account catalogue'); $this->role=$role;
        return array_map(fn($name)=>['blockname'=>$name,'moduleid'=>$name==='paused'?'paused':'fixture'], ['valid','hidden','broken','paused']);
    }
};
$purposes=[];
foreach (['mylearning'=>'readonly','myteaching'=>'author','myadmin'=>'admin'] as $module=>$role) {
    $editor=(new FixtureDashboardEditor())->forModule($module);
    $input=['contextcode'=>$module,'csrf_token'=>'valid','revision'=>'original','blockid'=>'block|valid|fixture','side'=>'left','direction'=>'up'];
    check(count($editor->options(false))===2 && $objects['dbmoduleblocks']->role===$role,'role-filtered available choices');
    $before=$objects['dbcontextblocks']->writes;
    check($editor->handle('addblock',$input,'GET')['status']===405,'GET denied');
    foreach ([null,[], 'expired'] as $token) {$bad=$input;$bad['csrf_token']=$token;check($editor->handle('addblock',$bad,'POST')['code']==='block_csrf','token checked');}
    foreach (['root','other',[],null] as $scope) {$bad=$input;$bad['contextcode']=$scope;check(!$editor->handle('removeblock',$bad,'POST')['ok'],'foreign scope denied');}
    foreach (['block|hidden|fixture','block|broken|fixture','block|paused|paused','block|id|contentblocks','dynamicblock|foreign|fixture'] as $id) {$bad=$input;$bad['blockid']=$id;check(!$editor->handle('renderblock',$bad,'POST')['ok'],'unoffered block denied');}
    $objects['user']->persisted=false;check(!$editor->handle('removeblock',$input,'POST')['ok'] && $editor->options(false)===[] && $editor->settings('x')==='','revoked cached administrator denied');$objects['user']->persisted=true;
    $objects['user']->logged=false;check(!$editor->handle('removeblock',$input,'POST')['ok'],'anonymous denied');$objects['user']->logged=true;
    check($objects['dbcontextblocks']->writes===$before,'denials do not persist');
    check($editor->handle('renderblock',$input,'POST')['ok'],'preview permitted');
    check($editor->handle('addblock',$input,'POST')['ok'],'checked save delegated');
    $purposes[]=$editor->purpose($module);
    try {$editor->forModule('myadmin');throw new LogicException('scope changed');}catch(InvalidArgumentException $expected){}
}
check(count(array_unique($purposes))===3,'dashboard CSRF purposes separated');
$editor=new FixtureDashboardEditor();check(!$editor->canEdit(),'unconfigured denied');
try {$editor->forModule('root');throw new LogicException('unknown scope accepted');}catch(InvalidArgumentException $expected){}
echo "PASS three dashboard roles, current administrators, fixed scopes, CSRF, registered/available choices and shared persistence\n";
