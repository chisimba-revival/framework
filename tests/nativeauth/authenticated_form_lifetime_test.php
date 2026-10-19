<?php
/** Long-lived authenticated forms retain origin protection across tabs and retries.
 * @author Derek Keats <derek@dkeats.com>
 */
require_once __DIR__.'/../../app/core_modules/security/classes/nativeauth/csrftokenservice.php';
require_once __DIR__.'/../../app/core_modules/security/classes/nativeauth/nativesessionservice.php';
/** Isolated session storage, equivalent to the security composition namespace.
 * @author Derek Keats <derek@dkeats.com>
 */
class FormLifetimeBackend {
    public $values=[];
    public function getSession($key,$default=null,$module='security'){return $this->values[$key]??$default;}
    public function setSession($key,$value,$module='security'){$this->values[$key]=$value;}
    public function unsetSession($key,$module='security'){unset($this->values[$key]);}
}
function formCheck($condition,$message){if(!$condition)throw new RuntimeException($message);}
$now=1000; $backend=new FormLifetimeBackend();
$sessions=new NativeSessionService($backend,fn()=>true,fn()=>1000);
$identity=function()use($sessions){return $sessions->isAuthenticated()?$sessions->getUserId().':'.$sessions->get('nativeAuthFormEpoch'):null;};
$csrf=new CsrfTokenService($backend,900,function()use(&$now){return $now;},$identity);
$anonymous=$csrf->issue('login'); $now+=901;
formCheck(!$csrf->consume('login',$anonymous),'Anonymous token expired');
$sessions->establish('synthetic-user'); $original=$csrf->issue('events');
$size=strlen(serialize($backend->values));
for($i=0;$i<1000;$i++)$csrf->issue('events');
formCheck(strlen(serialize($backend->values))===$size,'Repeated renders use bounded storage');
$now+=365*86400;
formCheck($csrf->consume('events',$original),'Original form survives time and other tabs');
formCheck($csrf->consume('events',$original),'Other controls and validation retries remain usable');
formCheck(!$csrf->consume('different-context',$original),'Context isolation');
formCheck(!$csrf->consume('events',str_repeat('0',64)),'Forged token denied');
formCheck(!$csrf->consume('events',[$original]),'Non-string token denied');
$other=new CsrfTokenService(new FormLifetimeBackend(),900,null,$identity);
formCheck(!$other->consume('events',$original),'Different browser session denied');
$sessions->regenerateIdentifier(); formCheck($csrf->consume('events',$original),'Routine session-ID rotation preserves the login');
$sessions->destroy(); formCheck(!$csrf->consume('events',$original),'Logout revokes forms');
$sessions->establish('synthetic-user'); formCheck(!$csrf->consume('events',$original),'Same-user login at identical timestamp cannot revive old forms');
$fresh=$csrf->issue('events'); formCheck($csrf->consume('events',$fresh),'New login can save');
$sessions->establish('other-user'); formCheck(!$csrf->consume('events',$fresh),'Account switch revokes forms');
echo "PASS: year-long editing, 1,000 renders, retries, bounded storage, context/session isolation, forgery rejection, logout and re-login revocation.\n";
