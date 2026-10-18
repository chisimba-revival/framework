<?php
/**
 * Request-local event data passed to Chisimba observers.
 *
 * @author Derek Keats
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class ChisimbaEventNotification
{
    private $sender;
    private $name;
    private $info;
    private $cancelled = false;
    private $deliveries = 0;

    public function __construct($sender, $name, $info = array())
    {
        $this->sender = $sender;
        $this->name = $name;
        $this->info = $info;
    }

    public function getNotificationObject() { return $this->sender; }
    public function getNotificationName() { return $this->name; }
    public function getNotificationInfo() { return $this->info; }
    public function getNotificationCount() { return $this->deliveries; }
    public function increaseNotificationCount() { ++$this->deliveries; }
    public function cancelNotification() { $this->cancelled = true; }
    public function isNotificationCancelled() { return $this->cancelled; }
}
