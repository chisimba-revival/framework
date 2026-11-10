<?php
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {}
require dirname(__DIR__).'/classes/coursecatalogue_class_inc.php';
class CatalogueMembershipFixture extends coursecatalogue {
    public $entitled=false;
    public function uri($args,$module) { return http_build_query($args); }
    public function getObject($name,$module) {
        if ($name==='altconfig') return new class {function getItem($key){return 'https://example.test/';}};
        if ($name==='accesspolicyservice') return new class($this->entitled) {
            public function __construct(private $allowed) {}
            public function resolve($request) {return ['allowed'=>$this->allowed];}
        };
        throw new RuntimeException('Unexpected service');
    }
}
$c=new CatalogueMembershipFixture();
$c->objUser=new class {
    public $logged=true;
    function isLoggedIn(){return $this->logged;}
    function isAdmin(){return false;}
    function userId(){return 'fixture';}
};
$c->objUserContext=new class {
    public $member=true;
    function isContextMember($user,$course){return $this->member && $course==='private-course';}
};
$c->objLanguage=new class {function languageText($key,$module=null){return $key;} function code2Txt($key,$module=null){return $key;}};
$method=new ReflectionMethod(coursecatalogue::class,'actionFor');
$course=['contextcode'=>'private-course','access'=>'Private','access_policy'=>'private','private_admission_mode'=>'manual_review'];
function expectAction($condition,$label){if(!$condition)throw new RuntimeException($label);}
$action=$method->invoke($c,$course);
expectAction(str_contains($action['url']??'','joincontext'),'Existing private member can enter');
$c->objUserContext->member=false;
$action=$method->invoke($c,$course);
expectAction(($action['type']??'')==='notice','Non-member without entitlement still needs admission');
$c->entitled=true;
$action=$method->invoke($c,$course);
expectAction(str_contains($action['url']??'','joincontext'),'Explicit entitlement still grants entry');
$c->objUser->logged=false;
$action=$method->invoke($c,$course);
expectAction(($action['url']??'')==='https://example.test/','Anonymous visitor must sign in');
echo "PASS: private catalogue membership, outsider, entitlement and anonymous cases\n";
