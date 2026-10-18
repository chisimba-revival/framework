<?php
require_once __DIR__.'/../classes/abuseprotectionservice.php';
require_once __DIR__.'/../classes/trustedclientaddress_class_inc.php';
class BudgetFixture implements AbuseEventRepositoryInterface
{
    public $events=[];
    public function countFailures($a,$s,$t) { return 0; }
    public function record(array $e) { $this->events[]=$e; return true; }
    public function purgeExpired($t) { return 0; }
    public function reserveBudgets($lock,array $budgets) {
        foreach($budgets as $b) {
            $count=0; foreach($this->events as $e) if($e['action_key']===$b['action_key'] && $e['subject_hash']===$b['subject_hash'] && $e['occurred_at']>$b['since'])$count++;
            if($count>=$b['limit'])return false;
        }
        foreach($budgets as $b)$this->record($b);return true;
    }
}
function check($ok,$message) { if(!$ok)throw new RuntimeException($message); }
$repo=new BudgetFixture();$now=100000;$service=new AbuseProtectionService($repo,str_repeat('t',32),function()use(&$now){return $now;});
$limits=[['dimension'=>'ip','seconds'=>3600,'count'=>2],['dimension'=>'account','seconds'=>3600,'count'=>1],['dimension'=>'site','seconds'=>3600,'count'=>3]];
check($service->admit('registration.create',['ip'=>'192.0.2.1','account'=>'one@example.test','session'=>'a'],$limits)->isAllowed(),'first submission');
check(!$service->admit('registration.create',['ip'=>'192.0.2.2','account'=>'ONE@example.test','session'=>'b'],$limits)->isAllowed(),'email limit across IPs');
check($service->admit('registration.create',['ip'=>'192.0.2.1','account'=>'two@example.test','session'=>'c'],$limits)->isAllowed(),'second submission');
check(!$service->admit('registration.create',['ip'=>'192.0.2.1','account'=>'three@example.test','session'=>'new'],$limits)->isAllowed(),'IP limit across identities and sessions');
check($service->admit('registration.create',['ip'=>'192.0.2.3','account'=>'three@example.test'],$limits)->isAllowed(),'third site submission');
check(!$service->admit('registration.create',['ip'=>'192.0.2.4','account'=>'four@example.test'],$limits)->isAllowed(),'site limit');
check(!str_contains(json_encode($repo->events),'example.test')&&!str_contains(json_encode($repo->events),'192.0.2'),'no raw identifiers');
$now+=3600;check($service->admit('registration.create',['ip'=>'192.0.2.1','account'=>'one@example.test'],$limits)->isAllowed(),'sliding window boundary');
$server=['REMOTE_ADDR'=>'192.0.2.4','HTTP_X_FORWARDED_FOR'=>'198.51.100.1'];
check(TrustedClientAddress::resolve($server,'')==='192.0.2.4','untrusted forwarding ignored');
check(TrustedClientAddress::resolve($server,'192.0.2.0/24')==='198.51.100.1','trusted network');
$server['HTTP_X_FORWARDED_FOR']='203.0.113.5,198.51.100.1';
check(TrustedClientAddress::resolve($server,'192.0.2.4')==='198.51.100.1','spoofed left address ignored');
$server['HTTP_X_FORWARDED_FOR']='bad,198.51.100.1';check(TrustedClientAddress::resolve($server,'192.0.2.4')==='192.0.2.4','malformed chain refused');
check(TrustedClientAddress::resolve(['REMOTE_ADDR'=>'2001:db8::1','HTTP_X_FORWARDED_FOR'=>'2001:db9::5'],'2001:db8::/32')==='2001:db9::5','IPv6 network');
echo "PASS: independent submission budgets and trusted proxies\n";
