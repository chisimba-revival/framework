<?php
/**
 * Synchronous, request-local Chisimba events, owned by the engine.
 *
 * The public observer/notification vocabulary is retained for existing modules.
 * No PEAR classes, globals or process-wide singleton are required. Named events
 * are retained for late named observers unless the caller disables retention.
 * This is an in-memory notification service, not a durable job queue.
 *
 * @author Derek Keats
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
require_once __DIR__ . '/nativeeventnotification.php';

class ChisimbaEventDispatcher
{
    private $name;
    private $observers = array();
    private $pending = array();

    public function __construct($name = '__default') { $this->name = $name; }
    public function getName() { return $this->name; }

    /**
     * Keep historical class/method identity: repeated service registration
     * replaces the callback, preventing duplicate activity-stream writes.
     * Closures and invokable objects additionally use their object identity.
     */
    private function observerKey($callback)
    {
        if (is_array($callback)) {
            return (is_object($callback[0]) ? get_class($callback[0]) : $callback[0])
                . '::' . $callback[1];
        }
        return is_object($callback) ? spl_object_hash($callback) : $callback;
    }

    private function matchesClass($filter, $actual)
    {
        return empty($filter) || strcasecmp($filter, $actual) === 0;
    }

    /** Register or replace a callback; an empty name observes future events. */
    public function addObserver($callback, $name = '', $class = null)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Event observer must be callable');
        }
        $this->observers[$name][$this->observerKey($callback)] = array($callback, $class);
        foreach ($this->pending[$name] ?? array() as $event) {
            $this->deliver($callback, $class, $event);
        }
    }

    /** Create and dispatch an event, optionally retaining it for later observers. */
    public function post($sender, $name, $info = array(), $pending = true)
    {
        $event = new ChisimbaEventNotification($sender, $name, $info);
        return $this->postNotification($event, $pending);
    }

    /** Named observers run before global observers; callback errors propagate. */
    public function postNotification($event, $pending = true)
    {
        $name = $event->getNotificationName();
        if ($pending === true) { $this->pending[$name][] = $event; }
        foreach (array($name, '') as $bucket) {
            // Snapshot keys, but resolve callbacks at delivery time so a callback
            // can remove or replace another registration during dispatch.
            foreach (array_keys($this->observers[$bucket] ?? array()) as $key) {
                if ($event->isNotificationCancelled()) { return $event; }
                if (!isset($this->observers[$bucket][$key])) { continue; }
                list($callback, $class) = $this->observers[$bucket][$key];
                $this->deliver($callback, $class, $event);
            }
        }
        return $event;
    }

    private function deliver($callback, $class, $event)
    {
        if (!$event->isNotificationCancelled()
            && $this->matchesClass($class, get_class($event->getNotificationObject()))) {
            call_user_func_array($callback, array(&$event));
            $event->increaseNotificationCount();
        }
    }

    public function observerRegistered($callback, $name = '', $class = null)
    {
        $entry = $this->observers[$name][$this->observerKey($callback)] ?? null;
        return $entry !== null && (empty($class)
            || ($entry[1] !== null && strcasecmp($entry[1], $class) === 0));
    }

    public function removeObserver($callback, $name = '', $class = null)
    {
        if (!$this->observerRegistered($callback, $name, $class)) { return false; }
        unset($this->observers[$name][$this->observerKey($callback)]);
        if (empty($this->observers[$name])) { unset($this->observers[$name]); }
        return true;
    }

    public function getObservers($name = '', $class = null)
    {
        $keys = array();
        foreach ($this->observers[$name] ?? array() as $key => $entry) {
            if ($class === null || $entry[1] === null || strcasecmp($entry[1], $class) === 0) {
                $keys[] = $key;
            }
        }
        return $keys;
    }
}
