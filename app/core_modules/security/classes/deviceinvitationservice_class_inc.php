<?php
/** Single-use device invitations. Module policy controls management and grants;
 * canonical security services own identities, credentials and token lifecycle.
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class deviceinvitationservice extends dbTable
{
 public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_device_invitations',$pearDb,$errorCallback);}
 private function policy($module,$class){return $this->getObject($class,$module);}
 private function guard($module,$class){if(!$this->policy($module,$class)->mayManageInvitations())throw new DomainException('forbidden');}
 private function transactional(){require_once dirname(__DIR__,3).'/classes/core/chisimbadatabaseschema.php';$schema=new ChisimbaDatabaseSchema($this);foreach(['tbl_device_invitations','tbl_users','tbl_perms_perm_users','tbl_perms_groupusers','tbl_rest_refresh_tokens','tbl_rest_identity_epoch'] as $table)$schema->requireTransactional($table);}
 private function pinHash($module,$pin){
  require_once dirname(__DIR__,3).'/classes/core/installationmasterkeyprovider.php';
  $key=(new InstallationMasterKeyProvider())->deriveKey('device-invitation-pin-v1');
  return hash_hmac('sha256',$module.'|'.$pin,$key);
 }
 private function admitAttempt($module){
  // Apache's trusted proxy configuration owns REMOTE_ADDR; never trust a client-supplied XFF here.
  $ip=(string)($_SERVER['REMOTE_ADDR']??'');if(!filter_var($ip,FILTER_VALIDATE_IP))$ip='unknown';
  $packed=@inet_pton($ip);if($packed!==false&&strlen($packed)===16)$ip=bin2hex(substr($packed,0,8)).'/64';
  $decision=$this->getObject('abuseprotectionprovider','abuseprotection')->admit('device_activation.'.$module,['ip'=>$ip],[
   ['dimension'=>'ip','seconds'=>900,'count'=>5],
   ['dimension'=>'ip','seconds'=>3600,'count'=>10],
   ['dimension'=>'site','seconds'=>3600,'count'=>30]
  ]);
  if(!$decision->isAllowed())throw new DomainException('activation_rate_limit');
 }
 public function items($module,$class){$this->guard($module,$class);return $this->selectExactPrepared('SELECT id,label,status,expires_at,created_at FROM tbl_device_invitations WHERE module_id=? ORDER BY created_at DESC LIMIT 200',[$module]);}
 public function issue($module,$class,$label,$id=''){
  $this->guard($module,$class);$this->transactional();$label=trim($label);
  if($label===''||mb_strlen($label)>80||($id!==''&&!preg_match('/^[a-f0-9]{32}$/D',$id)))throw new InvalidArgumentException('Invalid invitation');
  // Serialise PIN allocation and the ten-pending-invitation cap across workers.
  $lock='invite-pin:'.substr(hash('sha256',$module),0,32);
  $acquired=$this->selectExactPrepared('SELECT GET_LOCK(CONCAT(LEFT(DATABASE(),12),?),2) AS acquired',[$lock]);
  if((int)($acquired[0]['acquired']??0)!==1)throw new RuntimeException('Invitation issuance busy');
  try{return $this->transactionWithSavepoint(function()use($module,$class,$label,$id){
   $pending=$this->selectExactPrepared("SELECT COUNT(*) AS total FROM tbl_device_invitations WHERE module_id=? AND status='pending' AND expires_at>? AND id<>?",[$module,gmdate('Y-m-d H:i:s'),$id]);
   if((int)$pending[0]['total']>=10)throw new DomainException('invite_pending_limit');
   $row=$id!==''?($this->selectExactPrepared('SELECT * FROM tbl_device_invitations WHERE id=? AND module_id=? FOR UPDATE',[$id,$module])[0]??null):null;
   if($id!==''&&!$row)throw new DomainException('not_found');
   if(!$row){
    $id=bin2hex(random_bytes(16));$users=$this->getObject('userservice','security');$uid=$users->generateUserId();
    $created=$this->getObject('userprovisioningservice','security')->createLocalUser(['userid'=>$uid,'username'=>'pilot_'.bin2hex(random_bytes(10)),'firstname'=>$label,'surname'=>'Pilot','howcreated'=>'registration-service','isactive'=>false],bin2hex(random_bytes(32)));
    if(empty($created['ok']))throw new RuntimeException('Invitation identity could not be created');
   }else{$uid=$row['user_id'];$this->invalidate($module,$class,$uid);}
   $hash=null;
   for($attempt=0;$attempt<20;$attempt++){
    $code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);$candidate=$this->pinHash($module,$code);
    if(!$this->selectExactPrepared('SELECT id FROM tbl_device_invitations WHERE code_hash=?',[$candidate])){$hash=$candidate;break;}
   }
   if($hash===null)throw new RuntimeException('PIN allocation failed');
   $expires=gmdate('Y-m-d H:i:s',time()+3600);
   if($row)$this->executeExactPrepared("UPDATE tbl_device_invitations SET label=?,code_hash=?,expires_at=?,status='pending' WHERE id=?",[$label,$hash,$expires,$id]);
   else $this->executeExactPrepared('INSERT INTO tbl_device_invitations (id,module_id,user_id,label,code_hash,expires_at,status,created_by,created_at) VALUES (?,?,?,?,?,?,?, ?,?)',[$id,$module,$uid,$label,$hash,$expires,'pending',(string)$this->getObject('user','security')->userId(),gmdate('Y-m-d H:i:s')]);
   return ['id'=>$id,'code'=>$code,'label'=>$label,'expires_at'=>$expires];
  });}finally{$released=$this->selectExactPrepared('SELECT RELEASE_LOCK(CONCAT(LEFT(DATABASE(),12),?)) AS released',[$lock]);if((int)($released[0]['released']??0)!==1)throw new RuntimeException('Invitation lock release failed');}
 }
 private function invalidate($module,$class,$uid){
  $this->policy($module,$class)->revokeInvitationUser($uid);
  $result=$this->getObject('userservice','security')->setActive($uid,false);if(empty($result['ok']))throw new RuntimeException('Account revocation failed');
  $auth=$this->getObject('tokenauthservice','security');$auth->revokeAllUserTokens($uid);$auth->raiseEpoch($uid);
 }
 public function revoke($module,$class,$id){
  $this->guard($module,$class);$this->transactional();
  $this->transactionWithSavepoint(function()use($module,$class,$id){$row=$this->selectExactPrepared('SELECT * FROM tbl_device_invitations WHERE id=? AND module_id=? FOR UPDATE',[$id,$module])[0]??null;if(!$row)throw new DomainException('not_found');$this->invalidate($module,$class,$row['user_id']);$this->executeExactPrepared("UPDATE tbl_device_invitations SET status='revoked' WHERE id=?",[$id]);});
 }
 public function activate($module,$class,$code,$device){
  // Commit attempt reservations before the activation transaction; failures must consume attempts.
  $this->admitAttempt($module);
  $code=strtoupper(str_replace(['-',' '],'',$code));
  if((!preg_match('/^[0-9]{6}$/D',$code)&&!preg_match('/^[A-F0-9]{32}$/D',$code))||!preg_match('/^[a-f0-9-]{36}$/D',$device))throw new DomainException('invalid_activation');
  $this->transactional();
  return $this->transactionWithSavepoint(function()use($module,$class,$code,$device){
   $row=$this->selectExactPrepared('SELECT * FROM tbl_device_invitations WHERE module_id=? AND code_hash=? FOR UPDATE',[$module,strlen($code)===6?$this->pinHash($module,$code):hash('sha256',$code)])[0]??null;
   if(!$row||$row['status']!=='pending'||$row['expires_at']<=gmdate('Y-m-d H:i:s'))throw new DomainException('invalid_activation');
   $uid=$row['user_id'];$users=$this->getObject('userservice','security');if(!$users->findByUserId($uid))throw new DomainException('invalid_activation');
   $active=$users->setActive($uid,true);if(empty($active['ok']))throw new RuntimeException('Activation failed');
   $this->policy($module,$class)->grantInvitationUser($uid);
   $tokens=$this->getObject('tokenauthservice','security')->issueTokens($uid,['device_id'=>$device]);
   $this->executeExactPrepared("UPDATE tbl_device_invitations SET status='activated' WHERE id=?",[$row['id']]);
   return ['tokens'=>$tokens,'user'=>['id'=>$uid,'username'=>$row['label']]];
  });
 }
}
