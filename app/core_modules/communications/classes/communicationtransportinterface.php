<?php
/**
 * Canonical transport boundary for queued communications.
 *
 * @author  Derek Keats
 * @package communications
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

interface CommunicationTransportInterface
{
    /** Return an array containing ok, code and optional providerReference/error. */
    public function deliver(array $message);
}
?>
