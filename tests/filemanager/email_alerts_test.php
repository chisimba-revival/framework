<?php
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
class ChisimbaObject {
 public array $services=[];
 function getObject($name,$module) {return $this->services[$module.'/'.$name] ?? throw new RuntimeException('Unexpected service '.$module.'/'.$name);}
 function uri($args) {return 'https://fixture.invalid/?action='.$args['action'].'&amp;folder='.$args['folder'];}
}
require __DIR__.'/../../app/core_modules/filemanager/classes/emailutils_class_inc.php';
$mail=new class {public array $messages=[]; public bool $ok=true; function queueEmail($message){$this->messages[]=$message;return ['ok'=>$this->ok];}};
$alerts=new emailutils();
$alerts->services=[
 'communications/communicationservice'=>$mail,
 'utilities/washout'=>new class {function parseText($text){return $text;}},
];
$alerts->objManageGroups=new class {function contextUsers($role,$context,$fields) {return $role==='Students' ? [['email'=>'person@example.invalid','firstname'=>'Pat','surname'=>'Bird']] : [['emailaddress'=>'PERSON@example.invalid','firstname'=>'Pat','surname'=>'Bird'],['email'=>'other@example.invalid','firstname'=>'Sam','surname'=>'Bird']];}};
$alerts->objSysConfig=new class {function getValue($name,$module){return $name==='FILECREATE_ALERT_SUB' ? 'Updated {course}' : '<b>{firstname} {lastname}</b> {course} {instructor} {link}';}};
$alerts->objUser=new class {function getTitle(){return 'Dr';} function fullname(){return 'Test Instructor';}};
foreach (['sendFolderEmailAlert','sendFileEmailAlert'] as $method) {
 $mail->messages=[]; $mail->ok=true;
 if (!$alerts->$method('fixture','birds','Bird course') || count($mail->messages)!==2) throw new RuntimeException('Queue/dedup failed');
 foreach ($mail->messages as $message) {
  if ($message['subject']!=='Updated Bird course' || str_contains($message['text'],'{') || str_contains($message['text'],'<b>') || str_contains($message['text'],'amp;') || isset($message['from'])) throw new RuntimeException('Content/central sender contract failed');
 }
 $mail->ok=false;
 if ($alerts->$method('fixture','birds','Bird course')!==false) throw new RuntimeException('Queue failure hidden');
}
if(class_exists('PEAR',false)||class_exists('mailer',false)) throw new RuntimeException('Legacy mail loaded');
echo "PASS: File Manager queues personalised alerts, deduplicates roles, uses central sender and reports failure without PEAR\n";
