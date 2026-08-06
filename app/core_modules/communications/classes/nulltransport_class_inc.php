<?php
/**
 * Deterministic transport for installation proof and automated tests.
 *
 * @author  Derek Keats
 * @package communications
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
require_once dirname(__FILE__) . '/communicationtransportinterface.php';

class nulltransport extends ChisimbaObject implements CommunicationTransportInterface
{
    public function init() {}

    public function deliver(array $message)
    {
        return array(
            'ok' => true,
            'code' => 202,
            'providerReference' => 'null:' . (isset($message['id']) ? $message['id'] : 'unknown'),
        );
    }
}
?>
