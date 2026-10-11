<?php
/** Real content/outbox persistence under each disposable dbTable driver; no transport. */
require_once '/framework/app/core_modules/utilities/classes/dateandtime_class_inc.php';
require_once '/framework/app/core_modules/communications/classes/communicationservice_class_inc.php';
class ExpiryContentFixture extends dbTable {
 public bool $failUpdate=false;
 public function update($field,$value,$fields,$tablename='') {return $this->failUpdate?false:parent::update($field,$value,$fields,$tablename);}
}
class ExpiryCommunicationFixture extends communicationservice {
 public bool $allowed=true;
 public function getObject($name,$module) {return new class($this->allowed) {public function __construct(public bool $allowed){} public function recipientAllowed($recipient){return $this->allowed;}};}
}
class ExpiryDateFixture extends dateandtime {
 public array $services=[];
 public function getObject($name,$module) {return $this->services[$module.'/'.$name] ?? throw new RuntimeException('Unexpected expiry dependency '.$module.'/'.$name);}
}
$expiryStore=static function($class,$name)use($db,$table){$obj=new $class();(new ReflectionProperty(dbTable::class,'_db'))->setValue($obj,$db);$obj->dbLayer=$table->dbLayer;$obj->dbType='mysqli';$obj->_tableName=$name;$obj->_serverName='fixture';return $obj;};
$table->executeExactPrepared('CREATE TABLE expiry_content_fixture (id VARCHAR(32) PRIMARY KEY, notificationdate DATETIME NULL) ENGINE=InnoDB');
$table->executeExactPrepared('CREATE TABLE expiry_outbox_fixture (id VARCHAR(32) PRIMARY KEY,idempotency_key VARCHAR(191) UNIQUE,channel VARCHAR(32),transport VARCHAR(32),recipient VARCHAR(320),recipient_name VARCHAR(255),sender VARCHAR(320),sender_name VARCHAR(255),subject VARCHAR(998),body_text TEXT,body_html TEXT,metadata_json TEXT,status VARCHAR(24),attempts INT,available_at DATETIME,claimed_at DATETIME,sent_at DATETIME,last_error TEXT,date_created DATETIME,date_updated DATETIME) ENGINE=InnoDB');
$content=$expiryStore(ExpiryContentFixture::class,'expiry_content_fixture');
$queue=$expiryStore(ExpiryCommunicationFixture::class,'expiry_outbox_fixture');
$queue->objConfig=new class {public function getValue($name,$module){return $name==='COMMUNICATION_FROM_EMAIL'?'site@example.invalid':'Fixture site';}};
$service=new ExpiryDateFixture();
$service->services=['fixture/content'=>$content,'communications/communicationservice'=>$queue,
 'security/userservice'=>new class {public function findByUserId($id){return $id==='owner'?['emailaddress'=>'owner@example.invalid']:null;}}];
$service->objLanguage=new class {public function code2Txt($key,$module,$replacements){if($module!=='utilities')throw new RuntimeException('Wrong language module');return 'Content has expired on '.$replacements['kng'];}};
$service->objConfig=new class {public function getsiteName(){return 'Fixture site';}};
$content->insert(['id'=>'content','notificationdate'=>null]);
$notify=static fn($owner='owner')=>$service->sendExpiredMsg('content','fixture',$owner,'Birds','Expired body','content');
ensureBaseline(!$notify('missing'),'Missing owner accepted');
$queue->allowed=false;ensureBaseline(!$notify(),'Denied recipient accepted');
ensureBaseline($content->getRow('id','content')['notificationdate']===null,'Failure marked notification');
$queue->allowed=true;
$table->executeExactPrepared("CREATE TRIGGER expiry_queue_failure BEFORE INSERT ON expiry_outbox_fixture FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fixture queue failure'");
ensureBaseline(!$notify(),'Queue storage failure ignored');
$table->executeExactPrepared('DROP TRIGGER expiry_queue_failure');
ensureBaseline($content->getRow('id','content')['notificationdate']===null,'Queue storage failure marked notification');
$content->failUpdate=true;
ensureBaseline(!$notify(),'Failed timestamp update hidden');
ensureBaseline((int)$queue->getRecordCount()===1,'First queue insertion missing');
$content->failUpdate=false;ensureBaseline($notify(),'Retry failed');
ensureBaseline((int)$queue->getRecordCount()===1,'Retry duplicated accepted message');
$row=$queue->getAll()[0];
ensureBaseline($row['recipient']==='owner@example.invalid'&&$row['sender']==='site@example.invalid'&&$row['subject']==='Content has expired on Fixture site','Recipient/sender/subject contract');
ensureBaseline($row['status']==='queued'&&$row['sent_at']===null,'Queue acceptance presented as delivery');
ensureBaseline(!$notify()&&(int)$queue->getRecordCount()===1,'24-hour suppression failed');
$content->update('id','content',['notificationdate'=>date('Y-m-d H:i:s',time()-90000)]);
ensureBaseline($notify()&&(int)$queue->getRecordCount()===2,'Later notification suppressed');
ensureBaseline(!$service->sendExpiredMsg('content','fixture','owner','title','body','missing'),'Missing content accepted');
echo 'PASS content expiry: real '.$table->dbLayer." outbox, canonical recipient, queue failures, retry deduplication, cooldown and later notifications\n";
