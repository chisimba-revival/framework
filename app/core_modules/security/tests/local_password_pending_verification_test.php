<?php
require_once dirname(__DIR__).'/classes/nativeauth/localpasswordprovider.php';

final class PendingUserRepository implements NativeUserRepositoryInterface
{
    public function findByUsername($username){return array('user_id'=>'u1','username'=>$username,'password_hash'=>'hash','created_by'=>'registration-service');}
    public function findById($userId){return $this->findByUsername('molly');}
    public function isUserActive($userId){return false;}
    public function updatePasswordHash($userId,$passwordHash){return true;}
    public function recordSuccessfulLogin($userId,array $context=array()){return true;}
    public function recordFailedLogin($username,array $context=array()){return true;}
}
final class ExactPasswordVerifier implements NativePasswordVerifierInterface
{
    public function verify($plain,$hash,array $user=array()){return $plain==='correct';}
    public function needsRehash($hash){return false;}
    public function createHash($plain){return 'hash';}
    public function identifyHashScheme($hash){return 'password_hash';}
}
$provider=new LocalPasswordProvider(new PendingUserRepository(),new ExactPasswordVerifier());
$wrong=$provider->authenticate('molly','wrong');
$correct=$provider->authenticate('molly','correct');
if($wrong->getStatus()!==CanonicalAuthenticationResult::STATUS_INVALID_CREDENTIALS
    || $correct->getStatus()!==CanonicalAuthenticationResult::STATUS_INACTIVE
    || $correct->getReason()!=='pending_verification'){
    fwrite(STDERR,"FAIL: pending-account login distinction is unsafe or incorrect\n");exit(1);
}
echo "PASS: only a correct password reveals the pending verification journey.\n";
?>
