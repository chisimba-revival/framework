<?php
/** Optional notification integration must not load private UI for guests. @author Derek Keats */
class ChisimbaObject {
    public $authenticated = false;
    public $installed = false;
    public $loads = array();
    public function getObject($name, $module) {
        $this->loads[] = $name;
        if ($name === 'user') return new class($this->authenticated) {
            public function __construct(private $authenticated) {}
            public function isLoggedIn() { return $this->authenticated; }
        };
        if ($name === 'modules') return new class($this->installed) {
            public function __construct(private $installed) {}
            public function checkIfRegistered($name) { return $name === 'notifications' && $this->installed; }
        };
        if ($name === 'notificationtoolbar') return new class {
            public function show() { return 'PRIVATE_NOTIFICATION_CONTROL'; }
        };
        throw new RuntimeException('Unexpected dependency');
    }
}
require dirname(__DIR__) . '/classes/notificationmenu_class_inc.php';
foreach (array(array(false,true,false),array(true,false,false),array(true,true,true)) as [$authenticated,$installed,$visible]) {
    $menu = new notificationmenu(); $menu->authenticated=$authenticated; $menu->installed=$installed;
    $output=$menu->show();
    if (($output !== '') !== $visible || in_array('notificationtoolbar',$menu->loads,true) !== $visible) {
        throw new RuntimeException('Notification visibility boundary failed');
    }
}
echo "PASS: guests and missing installations never load notification UI; authenticated installations do.\n";
