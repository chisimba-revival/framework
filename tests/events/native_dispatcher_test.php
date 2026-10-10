<?php
/** Native ownership and safe callback mutation beyond the legacy caller contract. */
require __DIR__ . '/dispatcher_contract_test.php';
$first = new ChisimbaEventDispatcher(); $second = new ChisimbaEventDispatcher();
$seen = array();
$callback = static function ($event) use (&$seen) { $seen[] = $event->getNotificationName(); };
$first->addObserver($callback, 'isolated');
$second->post($sender, 'isolated');
checkEvent($seen, array(), 'engines do not share observers or pending events');
$first->post($sender, 'isolated', array(), false);
checkEvent($seen, array('isolated'), 'closure delivery');
checkEvent($first->removeObserver($callback, 'isolated'), true, 'closure removal');
$first->post($sender, 'isolated', array(), false);
checkEvent(count($seen), 1, 'removed callback not delivered');
$invalid = false;
try { $first->addObserver('not_a_real_event_callback'); } catch (InvalidArgumentException $e) { $invalid = true; }
checkEvent($invalid, true, 'invalid callback fails at registration');

$d = new ChisimbaEventDispatcher();
$seen = array();
$later = static function ($event) use (&$seen) { $seen[] = 'removed'; };
$d->addObserver(static function ($event) use ($d, $later, &$seen) {
    $seen[] = 'first'; $d->removeObserver($later, 'mutation');
}, 'mutation');
$d->addObserver($later, 'mutation');
$d->post($sender, 'mutation', array(), false);
checkEvent($seen, array('first'), 'removal during delivery is safe');

$d = new ChisimbaEventDispatcher();
$seen = array();
$d->addObserver(static function ($event) use ($d, $sender, &$seen) {
    $seen[] = $event->getNotificationName();
    if ($event->getNotificationName() === 'outer') { $d->post($sender, 'inner', array(), false); }
});
$d->post($sender, 'outer', array(), false);
checkEvent($seen, array('outer', 'inner'), 'reentrant synchronous posting');
checkEvent(class_exists('Event_Dispatcher', false), false, 'no legacy dispatcher loaded');
checkEvent(class_exists('Event_Notification', false), false, 'no legacy notification loaded');
echo "PASS: engine isolation, native callbacks, callback mutation, reentrant posting and PEAR independence\n";
