<?php
/** Opt-in fresh-install fixture. Queue only a synthetic address and roll it back. */
if(PHP_SAPI!=='cli'||getenv('CHISIMBA_INSTALLER_FIXTURE')!=='1')exit(64);
error_reporting(E_ALL);
set_error_handler(static function($s,$m,$f,$l){throw new ErrorException($m,0,$s,$f,$l);});
require __DIR__.'/../installer/installed_runtime_smoke.php';
$notice=$engine->getObject('dateandtime','utilities');
$queue=$engine->getObject('communicationservice','communications');
(new ChisimbaDatabaseSchema($queue))->requireTransactional('tbl_communications_outbox');
$key='expiry-fixture:'.bin2hex(random_bytes(12));
$queue->beginTransaction();
try {
 if(!$notice->mailUser('expiry-fixture@example.invalid',null,'Expiry fixture','Synthetic local notice',$key))throw new RuntimeException('Expiry queue failed');
 $message=$queue->messageForKey($key);$row=$queue->status($message['messageId']);
 if($row['subject']!=='Expiry fixture'||$row['recipient']!=='expiry-fixture@example.invalid'||$row['status']!=='queued')throw new RuntimeException('Expiry payload mismatch');
} finally {$queue->rollbackTransaction();}
if($queue->messageForKey($key)!==null)throw new RuntimeException('Fixture rollback failed');
echo "PASS installed dateandtime to Communications; synthetic queue row rolled back, no delivery\n";
