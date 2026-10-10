<?php
/** Behavioural contract shared by the legacy oracle and the native dispatcher. */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$GLOBALS['kewl_entry_point_run'] = true;
$root = dirname(__DIR__, 2);
$legacy = in_array('--legacy', $argv, true);
if ($legacy) {
    set_include_path($root . '/app/lib/pear' . PATH_SEPARATOR . get_include_path());
    require_once 'Event/Dispatcher.php';
    $dispatcherClass = 'Event_Dispatcher';
} else {
    require_once $root . '/app/classes/core/nativeeventdispatcher.php';
    $dispatcherClass = 'ChisimbaEventDispatcher';
}
$checks = 0;
function checkEvent($actual, $expected, $message) {
    global $checks;
    if ($actual !== $expected) throw new RuntimeException($message . ': ' . var_export($actual, true));
    ++$checks;
}
class EventContractSender {}
class EventContractChild extends EventContractSender {}
class EventContractObserver {
    public $seen = array();
    public $cancel = false;
    public $throw = false;
    public function receive($event) {
        $this->seen[] = array($event->getNotificationName(), $event->getNotificationInfo(), $event->getNotificationCount());
        if ($this->cancel) $event->cancelNotification();
        if ($this->throw) throw new RuntimeException('observer failure');
    }
    public function second($event) { $this->receive($event); }
}
$d = new $dispatcherClass('contract');
$sender = new EventContractSender();
$one = new EventContractObserver();
$two = new EventContractObserver();
$payload = array('title' => 'Fixture', 'description' => 'Unicode ✓', 'link' => '/fixture', 'contextcode' => 'fixture');
$d->addObserver(array($one, 'receive'), 'context');
$d->addObserver(array($one, 'second'));
$event = $d->post($sender, 'context', $payload);
checkEvent($event->getNotificationObject(), $sender, 'sender identity');
checkEvent($one->seen, array(array('context', $payload, 0), array('context', $payload, 1)), 'named before global and unchanged payload');
checkEvent($event->getNotificationCount(), 2, 'delivery count');
$d->addObserver(array($two, 'receive'), 'context');
checkEvent($two->seen, array(array('context', $payload, 2)), 'late named observer receives retained event');
checkEvent(count($d->getObservers('context')), 1, 'same class/method replaces earlier instance');
$d->post($sender, 'context', array(), false);
checkEvent(count($one->seen), 3, 'replaced observer no longer receives named event');
checkEvent(count($two->seen), 2, 'replacement receives next event');
checkEvent($d->observerRegistered(array($one, 'receive'), 'context'), true, 'registration identity remains class/method');
checkEvent($d->removeObserver(array($one, 'receive'), 'context'), true, 'remove same class callback');
checkEvent($d->removeObserver(array($one, 'receive'), 'context'), false, 'remove absent callback');

$d = new $dispatcherClass('pending');
$one = new EventContractObserver();
$d->post($sender, 'before', array('n' => 1));
$d->post($sender, 'before', array('n' => 2), false);
$d->addObserver(array($one, 'receive'));
checkEvent($one->seen, array(), 'global observers do not replay other named queues');
$d->addObserver(array($one, 'second'), 'before');
checkEvent($one->seen, array(array('before', array('n' => 1), 0)), 'only retained events replay');
$d->addObserver(array($one, 'second'), 'before');
checkEvent(count($one->seen), 2, 're-registration repeats pending delivery');

$d = new $dispatcherClass('filters');
$one = new EventContractObserver();
$d->addObserver(array($one, 'receive'), 'context', 'eventcontractsender');
$d->post($sender, 'context', array(), false);
$child = new EventContractChild();
$d->post($child, 'context', array(), false);
checkEvent(count($one->seen), 1, 'case-insensitive exact sender class; no subclass match');
checkEvent($d->observerRegistered(array($one, 'receive'), 'context', 'Other'), false, 'registration class filter');
checkEvent($d->removeObserver(array($one, 'receive'), 'context', 'Other'), false, 'removal class filter');
checkEvent($d->getObservers('context', 'Other'), array(), 'observer listing filter');
checkEvent($d->removeObserver(array($one, 'receive'), 'context', 'EventContractSender'), true, 'case-insensitive removal');

$d = new $dispatcherClass('cancel');
$one = new EventContractObserver(); $one->cancel = true;
$two = new EventContractObserver();
$d->addObserver(array($one, 'receive'), 'context');
$d->addObserver(array($two, 'second'));
$event = $d->post($sender, 'context');
checkEvent($event->isNotificationCancelled(), true, 'cancellation state');
checkEvent($event->getNotificationCount(), 1, 'cancelling observer counted');
checkEvent($two->seen, array(), 'cancellation stops global delivery');
$d->addObserver(array($two, 'second'), 'context');
checkEvent($two->seen, array(), 'cancelled pending event not replayed');

$d = new $dispatcherClass('failure');
$one = new EventContractObserver(); $one->throw = true;
$d->addObserver(array($one, 'receive'));
$caught = false;
try { $d->post($sender, 'context'); } catch (RuntimeException $e) { $caught = $e->getMessage() === 'observer failure'; }
checkEvent($caught, true, 'observer exception propagates');
checkEvent($one->seen[0][2], 0, 'callback sees pre-delivery count');
checkEvent($d->getName(), 'failure', 'dispatcher name');
// Preserve the legacy empty-name behaviour, including its two delivery buckets.
$d = new $dispatcherClass('empty-name');
$one = new EventContractObserver();
$d->addObserver(array($one, 'receive'));
$event = $d->post($sender, '', array(), false);
checkEvent($event->getNotificationCount(), 2, 'empty-name event traverses both buckets');
checkEvent($one->seen, array(array('', array(), 0), array('', array(), 1)), 'empty-name callback order');

$d = new $dispatcherClass('late-class-filter');
$one = new EventContractObserver();
$d->post($sender, 'context');
$d->addObserver(array($one, 'receive'), 'context', 'OtherSender');
checkEvent($one->seen, array(), 'late observer class filter');
$d->addObserver(array($one, 'receive'), 'context', 'EventContractSender');
checkEvent(count($one->seen), 1, 'late observer matching class');
echo 'PASS: ' . $checks . ' event behaviour checks (' . ($legacy ? 'PEAR oracle' : 'native') . ")\n";
