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
foreach (array('MODULE_ID: communications', 'COMMUNICATION_SENDGRID_API_KEY', 'COMMUNICATION_TRANSPORT') as $needle) {
    if (strpos($register, $needle) === false) { fwrite(STDERR, "Missing contract: $needle\n"); exit(1); }
}

foreach (array('PAGE: admin_system|||mod_communications_admin_testemail',
    'PAGE: admin_common|||mod_communications_admin_testemail',
    'ADMIN_SEARCH: email|mail|sendgrid|delivery|test email|communications|outbox|queue') as $needle) {
    if (!str_contains(file_get_contents(dirname(__DIR__) . '/register.conf'), $needle)) {
        fwrite(STDERR, "Missing Communications administration route: $needle\n");
        exit(1);
    }
}
$sendgrid = file_get_contents($root . '/classes/sendgridtransport_class_inc.php');
if (strpos($sendgrid, "getValue('COMMUNICATION_SENDGRID_API_KEY', 'communications')") === false || strpos($sendgrid, 'api.sendgrid.com/v3/mail/send') === false) {
    fwrite(STDERR, "SendGrid secret/endpoint contract failed\n"); exit(1);
}
echo "communications first-slice contracts: OK\n";
?>
