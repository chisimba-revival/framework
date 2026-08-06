<?php
/**
 * Static first-slice contract test.
 *
 * @author  Derek Keats
 * @package communications
 */
$root = dirname(__DIR__);
$required = array(
    'register.conf', 'classes/communicationservice_class_inc.php',
    'classes/communicationworker_class_inc.php', 'classes/nulltransport_class_inc.php',
    'classes/sendgridtransport_class_inc.php', 'classes/communicationtransportinterface.php',
    'sql/tbl_communications_outbox.sql', 'sql/tbl_communications_attempts.sql',
);
foreach ($required as $path) {
    if (!is_file($root . '/' . $path)) { fwrite(STDERR, "Missing: $path\n"); exit(1); }
}
$register = file_get_contents($root . '/register.conf');
foreach (array('MODULE_ID: communications', 'COMMUNICATION_SENDGRID_SECRET_REF', 'COMMUNICATION_TRANSPORT') as $needle) {
    if (strpos($register, $needle) === false) { fwrite(STDERR, "Missing contract: $needle\n"); exit(1); }
}
$sendgrid = file_get_contents($root . '/classes/sendgridtransport_class_inc.php');
if (strpos($sendgrid, "getenv(\$secretRef)") === false || strpos($sendgrid, 'api.sendgrid.com/v3/mail/send') === false) {
    fwrite(STDERR, "SendGrid secret/endpoint contract failed\n"); exit(1);
}
echo "communications first-slice contracts: OK\n";
?>
