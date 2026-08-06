<?php
/**
 * Executable contract and module-convention test for Abuse Protection.
 *
 * @category  Chisimba
 * @package   abuseprotection
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
require_once dirname(__FILE__) . '/../classes/abuseprotectionservice.php';

$moduleRoot = dirname(__FILE__) . '/..';
$requiredHeaders = array(
    'sql/tbl_abuse_events.sql',
    'classes/abuseeventrepositoryinterface.php',
    'classes/abuseprotectiondecision.php',
    'classes/abuseprotectionservice.php',
    'tests/abuseprotection_contract_test.php',
);
foreach ($requiredHeaders as $relativePath) {
    $headerSource = file_get_contents($moduleRoot . '/' . $relativePath);
    foreach (array('@category  Chisimba', '@package   abuseprotection',
        '@author    Derek Keats', '@copyright 2026 Derek Keats',
        '@license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License') as $requiredText) {
        if (strpos($headerSource, $requiredText) === false) {
            throw new RuntimeException('Missing Chisimba header field in ' . $relativePath . ': ' . $requiredText);
        }
    }
}
$registerSource = file_get_contents($moduleRoot . '/register.conf');
$requiredDescription = 'MODULE_DESCRIPTION: Canonical first-party abuse-decision, form-evidence and rate-limiting service based on the Chisimba security policy';
if (substr_count($registerSource, $requiredDescription) !== 1) {
    throw new RuntimeException('Canonical module description is missing or duplicated.');
}
final class MemoryAbuseEvents implements AbuseEventRepositoryInterface
{
    public $events=array();
    public function countFailures($a,$s,$since) { $n=0; foreach($this->events as $e)
        if($e['action_key']===$a && $e['subject_hash']===$s && $e['outcome']==='failure' && $e['occurred_at']>=$since) $n++; return $n; }
    public function record(array $e) { $this->events[]=$e; return true; }
    public function purgeExpired($now) { return 0; }
}
function ensureP317($ok,$message) { if(!$ok) throw new RuntimeException($message); }
$now=1000; $repo=new MemoryAbuseEvents(); $ids=0;
$service=new AbuseProtectionService($repo,str_repeat('k',32),function()use(&$now){return $now;},function()use(&$ids){return str_pad((string)++$ids,32,'0',STR_PAD_LEFT);});
$form=$service->issueFormEvidence('security.login'); $now=1002;
$context=array('ip'=>'127.0.0.1','account'=>'Admin');
ensureP317($service->evaluate('security.login',$context,$form)->isAllowed(),'valid form allowed');
$bot=$form; $bot['website']='spam';
ensureP317($service->evaluate('security.login',$context,$bot)->getStatus()==='reject','honeypot rejected');
for($i=0;$i<5;$i++) ensureP317($service->record('security.login',$context,false),'failure recorded');
ensureP317($service->evaluate('security.login',$context,$form)->getStatus()==='delay','limit delays');
ensureP317(strpos(json_encode($repo->events),'127.0.0.1')===false,'raw IP not stored');
$tampered=$form; $tampered['signature'][0]=$tampered['signature'][0]==='a'?'b':'a';
ensureP317($service->evaluate('security.login',array('ip'=>'other'),$tampered)->getStatus()==='reject','tamper rejected');
fwrite(STDOUT,"PASS: abuse protection contract
");
