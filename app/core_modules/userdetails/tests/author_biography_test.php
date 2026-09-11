<?php
/** Biography boundaries and course-role reuse. Run with PHP CLI. @author Derek Keats */
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__).'/classes/authorbiographyvalue_class_inc.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$valid = authorbiographyvalue::validate(array('biography'=>"  Bird ecology.\n\nTeaching outdoors. ", 'links'=>array(array('label'=>'Work','url'=>'https://example.org/about'))));
check(!$valid['errors'] && $valid['value']['biography'] === "Bird ecology.\n\nTeaching outdoors.", 'Normalisation');
foreach (array('', str_repeat('a',6001)) as $bio) check(isset(authorbiographyvalue::validate(array('biography'=>$bio))['errors']['biography']), 'Biography bounds');
foreach (array('javascript:alert(1)','http://example.org','https://user:pass@example.org','data:text/html,test','//example.org') as $url) {
    check(!authorbiographyvalue::safeUrl($url), 'Unsafe link '.$url);
}
check(isset(authorbiographyvalue::validate(array('biography'=>'Text','links'=>array_fill(0,6,array())))['errors']['links']), 'Link limit');
check(isset(authorbiographyvalue::validate(array('biography'=>'Text','links'=>array(array('url'=>'https://example.org'))))['errors']['links']), 'Label required');
class ChisimbaObject { public function getObject($name,$module) { return $GLOBALS['objects'][$name]; } public function loadClass($name,$module) {} }
require dirname(__DIR__).'/classes/authorbiographyservice_class_inc.php';
$GLOBALS['objects']['dbauthorbiographies'] = new class { public $saved; public $empty=false; public function forUser($id) { return array('biography'=>$this->empty ? '  ' : 'Bio '.$id,'links_json'=>'[]'); } public function saveForUser($id,$value) { $this->saved=$id; return true; } };
$GLOBALS['objects']['user'] = new class { public $logged=true; public function isLoggedIn(){ return $this->logged; } public function userId(){return 'owner';} public function hasCustomImage($id){return false;} };
$GLOBALS['objects']['usercontext'] = new class { public function getContextLecturers($code) { return array(array('userid'=>'b','firstname'=>'Z','surname'=>'B'),array('userid'=>'a','firstname'=>'A','surname'=>'A'),array('userid'=>'a','firstname'=>'A','surname'=>'A')); } };
$service=new authorbiographyservice();$service->init();
$service->saveOwn(array('userid'=>'victim','biography'=>'Good bio'));
check($GLOBALS['objects']['dbauthorbiographies']->saved==='owner','Submitted user ID must never control ownership');
check(!$service->needsBiography('owner'),'Completed biography has no reminder');
$GLOBALS['objects']['dbauthorbiographies']->empty=true;
check($service->needsBiography('owner'),'Whitespace biography needs reminder');
$GLOBALS['objects']['dbauthorbiographies']->empty=false;
$authors=$service->forCourse('course');check(count($authors)===2 && $authors[0]['userid']==='a','Deduplicated deterministic course authors');
$GLOBALS['objects']['user']->logged=false;
try { $service->saveOwn(array('biography'=>'Text')); throw new LogicException('Unauthenticated mutation accepted'); } catch (RuntimeException $e) { check($e->getMessage()==='Authentication required','Authentication boundary'); }
$GLOBALS['objects']['authorbiographyservice']=$service;
$GLOBALS['objects']['language']=new class {public function code2Txt($key,$module){return 'About the authors';}};
$GLOBALS['objects']['iconservice']=new class {public function render($name,$options){return '<svg aria-hidden="true"></svg>';}};
require dirname(__DIR__).'/classes/authorbiographyrenderer_class_inc.php';
$renderer=new authorbiographyrenderer();$renderer->init();
$person=array('userid'=>'owner','name'=>'<script>alert(1)</script>', 'biography'=>'<img src=x onerror=alert(1)>', 'links'=>array(array('label'=>'External','url'=>'javascript:alert(1)')));
$html=$renderer->person($person,true);
check(!str_contains($html,'<script>') && !str_contains($html,'<img src=x') && !str_contains($html,'javascript:'),'Public output escaping');
$person['links']=array(array('label'=>'External','url'=>'https://example.org'));
check(!str_contains($renderer->person($person,false),'https://example.org'),'Course cards omit external links');
echo "Author biography tests passed\n";
