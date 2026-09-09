<?php
/** Sender identity regression using the real queue normalisation and a fake store.
 * No database, worker, transport or email delivery is invoked.
 * @author Derek Keats
 * @package communications
 */
$GLOBALS['kewl_entry_point_run'] = true;
class dbTable {
    public $stored;
    public $siteName = 'KengaLearn';
    public function insert($message) { $this->stored = $message; return true; }
    public function getObject($name, $module) {
        return new class($this->siteName) {
            public function __construct(private $name) {}
            public function getSiteName() { return $this->name; }
        };
    }
}
require dirname(__DIR__) . '/classes/communicationservice_class_inc.php';
function verify($condition, $label) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . PHP_EOL;
}
foreach (array('' => 'KengaLearn', 'Chisimba' => 'KengaLearn', ' chisimba ' => 'KengaLearn',
    'KengaLearn Support' => 'KengaLearn Support') as $configured => $expected) {
    $service = new communicationservice();
    $service->objConfig = new class($configured) {
        public function __construct(private $name) {}
        public function getValue($key, $module) { return $key === 'COMMUNICATION_FROM_NAME' ? $this->name : 'verified@example.test'; }
    };
    $result = $service->queueEmail(array('to' => 'recipient@example.test', 'subject' => 'Maintenance', 'text' => 'Unplanned maintenance'));
    verify($result['ok'] && $service->stored['sender_name'] === $expected, 'sender identity for ' . var_export($configured, true));
    verify($service->stored['sender'] === 'verified@example.test', 'verified sender address retained');
    $service->siteName = 'Another learning site';
    $service->queueEmail(array('to' => 'recipient@example.test', 'subject' => 'Maintenance', 'text' => 'Notice'));
    verify($service->stored['sender_name'] === ($configured === 'KengaLearn Support' ? $expected : 'Another learning site'), 'default follows a changed site name');
}
