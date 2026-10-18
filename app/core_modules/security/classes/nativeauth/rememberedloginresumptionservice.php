<?php
require_once __DIR__.'/canonicalauthenticationresult.php';
/** Restore identity before dispatch; never cache or manufacture permissions. */
final class RememberedLoginResumptionService
{
    private $persistent;
    private $sessions;
    private $users;
    private $policy;
    private $context;

    public function __construct($persistent, NativeSessionServiceInterface $sessions, $users, $policy, $context)
    {
        $this->persistent=$persistent;
        $this->sessions=$sessions;
        $this->users=$users;
        $this->policy=$policy;
        $this->context=$context;
    }

    public function resume($now)
    {
        if ($this->sessions->isAuthenticated()) return true;
        $userId=$this->persistent->restoreAndRotate($now);
        if ($userId===false) return false;
        try {
            $user=$this->users->findByUserId($userId);
            if (!is_array($user) || (string)($user['userid']??'') !== $userId
                || !in_array(strtolower(trim((string)($user['isactive']??''))),array('1','true','yes','y','active','enabled'),true)
                || trim((string)($user['username']??''))==='') {
                $this->persistent->revokeAllForUser($userId,$now);
                return false;
            }
            $proof=CanonicalAuthenticationResult::success('remembered',$userId,$user['username']);
            $evaluation=$this->policy->evaluate($proof,$this->context->resolve($proof));
            // The existing cookie schema has no MFA assurance/policy-version claim.
            // Never treat it as an exemption from a currently required MFA challenge.
            if (!in_array($evaluation['status']??'',array('not_required','grace'),true)) {
                $this->persistent->clear();
                return false;
            }
            if (!$this->sessions->establish($userId,array('username'=>$user['username'],'provider'=>'remembered'))) {
                $this->persistent->clear();
                return false;
            }
            return true;
        } catch (Throwable $error) {
            $this->persistent->clear();
            throw $error;
        }
    }
}
