<?php
/** Execute real legacy activity consumers with isolated storage/transport seams. */
require __DIR__ . '/dispatcher_contract_test.php';
$modules = $argv[1] ?? dirname($root) . '/modules';
if (str_starts_with($modules, '--')) { $modules = dirname($root) . '/modules'; }
if (!is_file($modules . '/activitystreamer/classes/activityops_class_inc.php')) {
    throw new RuntimeException('Supply the application modules checkout as the first argument');
}
class ChisimbaObject {}
class dbTable extends ChisimbaObject {
    public $objUser;
    public $rows = array();
    public function insert($row) { $this->rows[] = $row; return 'fixture-id'; }
    public function now() { return '2026-10-10 12:00:00'; }
}
require $modules . '/activitystreamer/classes/activitydb_class_inc.php';
require $modules . '/activitystreamer/classes/activityops_class_inc.php';
class ActivityFixture extends activityops {
    public $objActDB;
    public $objFeeds;
    public function configure($db, $hub, $feed) {
        $this->objActDB = $db;
        $this->objPubSubHubbub = $hub;
        $this->objFeeds = $feed;
        $this->objAltConfig = new class { public function getsiteRoot() { return 'https://fixture.invalid/'; } };
    }
    public function uri($args) { return '/fixture'; }
}
$db = new activitydb();
$db->objUser = new class { public $id = 'fixture-user'; public function userId() { return $this->id; } };
$hub = new class { public $urls = array(); public function publish($url) { $this->urls[] = $url; } };
$feed = new class {
    public $items = array();
    public function setupFeed(...$args) {}
    public function addItem(...$args) { $this->items[] = $args; }
    public function output() { return 'fixture-feed'; }
};
$activity = new ActivityFixture(); $activity->configure($db, $hub, $feed);
$d = new $dispatcherClass('activity');
$d->addObserver(array($activity, 'postmade'));
$d->addObserver(array($activity, 'postmade'));
$payload = array('title' => 'Fixture title', 'description' => 'Fixture body', 'link' => '/fixture', 'author' => 'fallback-user');
$event = $d->post($activity, 'context', $payload);
checkEvent($db->rows, array(array('module' => 'context', 'description' => 'Fixture body', 'title' => 'Fixture title', 'createdon' => '2026-10-10 12:00:00', 'createdby' => 'fixture-user', 'contextcode' => null, 'link' => '/fixture')), 'actual consumer row and no duplicate insert');
checkEvent($hub->urls, array('https://fixture.invalid/index.php?module=activitystreamer'), 'hub URL captured without network');
$db->objUser->id = null;
$payload['contextcode'] = 'fixture-course';
$d->post($activity, 'discussion', $payload);
checkEvent($db->rows[1]['createdby'], 'fallback-user', 'author fallback');
checkEvent($db->rows[1]['contextcode'], 'fixture-course', 'course retained');
$activity->createFeeds($event);
checkEvent($feed->items, array(array('context', '/fixture', 'Fixture body', 'wwww.somewhere.com', 'fallback-user')), 'actual feed consumer payload');
echo "PASS: real activity database-row, hub and feed consumer contracts; no database or network writes\n";
