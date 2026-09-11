<?php
/** Publication and video boundaries, independent of runtime fixtures. @author Derek Keats */
$GLOBALS['kewl_entry_point_run']=true;
class ChisimbaObject {public function getObject($name,$module){return $GLOBALS['objects'][$name];}}
require dirname(__DIR__).'/classes/coursemarketingservice_class_inc.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach(array('javascript:alert(1)','https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ','https://user@youtube.com/watch?v=dQw4w9WgXcQ','https://example.org/embed','https://youtube.com/watch?v[]=abc') as $url) check(coursemarketingservice::videoEmbed($url)===null,'Unsafe video');
check(coursemarketingservice::videoEmbed('https://youtu.be/dQw4w9WgXcQ')==='https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ','YouTube');
check(coursemarketingservice::videoEmbed('https://vimeo.com/123456')==='https://player.vimeo.com/video/123456','Vimeo');
$GLOBALS['objects']['dbcoursemarketing']=new class {public $row=null;public $saves=0;public function forCourse($code){return $this->row;}public function savePage($code,$content,$published){$this->saves++;$this->row=array('content_json'=>json_encode($content),'published'=>(int)$published);return true;}};
$GLOBALS['objects']['user']=new class {public $allowed=true;public function isLoggedIn(){return $this->allowed;}public function isAdmin(){return false;}public function userId(){return 'author';}public function isContextLecturer($id,$code){return $code==='owned';}};
$service=new coursemarketingservice();$service->init();$course=array('contextcode'=>'owned','status'=>'Published');
check(!$service->page($course)['published'],'Default unpublished');
check($service->save($course,array('published'=>'1'))['errors']!==array(),'Introduction required');
check($GLOBALS['objects']['dbcoursemarketing']->saves===0,'Invalid publish did not save');
$service->save($course,array('published'=>'1','introduction'=>'Public introduction'));
check($service->page($course)['published'],'Explicit publication');
check(!$service->page(array_merge($course,array('status'=>'Unpublished')))['published'],'Unpublished course hidden');
$service->save($course,array('introduction'=>'Draft'));
check(!$service->page($course)['published'],'Unpublish retained content');
$GLOBALS['objects']['user']->allowed=false;
try {$service->save($course,array());throw new LogicException('Anonymous write allowed');}catch(RuntimeException $e){check($e->getMessage()==='Course author permission required','Permission enforced');}
echo "Course marketing tests passed\n";
require dirname(__DIR__).'/classes/coursecatalogue_class_inc.php';
class TestMarketingCatalogue extends coursecatalogue {
    public function uri($params,$module){return '/index.php?module='.$module.'&amp;'.http_build_query($params,'','&amp;');}
}
$GLOBALS['objects']['dbcontext']=new stdClass();$GLOBALS['objects']['contextimage']=new stdClass();
$GLOBALS['objects']['usercontext']=new class {public $codes=array();public function getUserContext($id){return $this->codes;}};
$GLOBALS['objects']['language']=new class {public function code2Txt($key,$module,$args=null,$fallback=''){return $fallback;}};
$GLOBALS['objects']['user']=new class {public $logged=false;public function isLoggedIn(){return $this->logged;}public function isAdmin(){return false;}public function userId(){return 'test';}public function isContextLecturer($id,$code){return false;}};
$GLOBALS['objects']['paymentcatalogservice']=new class {public function privateCourseProduct($code){return array('code'=>'course-product','billing_period'=>'one_off','current_price'=>array('amount_minor'=>25000,'currency'=>'ZAR'));}};
$GLOBALS['objects']['accesspolicyservice']=new class {public $allowed=false;public function resolve($input){return array('allowed'=>$this->allowed);}};
$catalogue=new TestMarketingCatalogue();$catalogue->init();
$paid=array('contextcode'=>'paid','access'=>'Private','access_policy'=>'private','private_admission_mode'=>'automatic_payment');
$action=$catalogue->marketingAction($paid);check($action['label']==='Buy now' && $action['hint']==='R250.00','Product price and purchase CTA');
$free=array_merge($paid,array('access_policy'=>'free'));check($catalogue->marketingAction($free)['label']==='Enrol','Free enrolment');
$GLOBALS['objects']['user']->logged=true;$GLOBALS['objects']['usercontext']->codes=array('paid');$catalogue->init();
check($catalogue->marketingAction($paid)['label']==='Continue learning','Member never asked to buy');
$GLOBALS['objects']['usercontext']->codes=array();$GLOBALS['objects']['accesspolicyservice']->allowed=true;$catalogue->init();
check($catalogue->marketingAction($paid)['label']==='Continue learning','Entitled user never asked to buy again');
echo "Marketing CTA tests passed\n";
