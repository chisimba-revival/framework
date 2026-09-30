<?php
/**
 * Execute real controller policies with isolated identity/context fixtures.
 * This is not a substitute for HTTP/session and persisted-role journeys.
 * @author Derek Keats
 */
$GLOBALS['kewl_entry_point_run'] = true;
class ChisimbaObject
{
    public $moduleName = 'fixture';
    public function nextAction(...$args) { return 'denied'; }
}
class controller extends ChisimbaObject
{
    public static $services = array();
    public $params = array();
    public $session = array();
    public function getObject($name, $module) { return self::$services[$name]; }
    public function getParam($name, $default = null) { return $this->params[$name] ?? $default; }
    public function getSession($name, $default = null) { return $this->session[$name] ?? $default; }
    public function requiresLogin($action) { return true; }
}
function check($condition, $label)
{
    if (!$condition) { throw new RuntimeException($label); }
}
$root = dirname(__DIR__, 2) . '/app';
require $root . '/classes/core/access_class_inc.php';
require $root . '/core_modules/contextgroups/controller.php';
require $root . '/core_modules/contextpermissions/controller.php';
$user = new class {
    public $admin = false;
    public $courses = array();
    public function userId() { return 'fixture-user'; }
    public function isAdmin() { return $this->admin; }
    public function isContextLecturer($id, $course) {
        check($id === 'fixture-user', 'Policy must check the current user');
        return in_array($course, $this->courses, true);
    }
};
$context = new class {
    public function getContextCode() { return 'fixture-course'; }
};
controller::$services = array(
    'user' => $user, 'dbcontext' => $context,
    'groupservice' => new stdClass(), 'identityservice' => new stdClass(),
    'language' => new class { public function code2Txt($code, ...$args) { return $code; } },
);
$groups = new contextgroups();
$groups->init();
$permissions = new contextpermissions();
$token = str_repeat('a', 64);
$groups->params = array('context' => 'fixture-course', 'membershiptoken' => $token);
$groups->session = array('membershipToken' => $token);
$_SERVER['REQUEST_METHOD'] = 'POST';
$validate = new ReflectionMethod(contextgroups::class, 'mutationValidationError');
foreach (array('anonymous', 'outsider', 'student', 'other-course-lecturer', 'lecturer', 'administrator', 'revoked') as $role) {
    $user->admin = $role === 'administrator';
    $user->courses = $role === 'lecturer' ? array('fixture-course')
        : ($role === 'other-course-lecturer' ? array('other-course') : array());
    $allowed = in_array($role, array('lecturer', 'administrator'), true);
    foreach (array(null, 'addusers', 'removeuser', 'bulkupdatestudents', 'removeallstudents', 'importstudents') as $action) {
        check($groups->isValid($action) === $allowed, "$role membership policy");
    }
    check(!$groups->isValid('unknown-action'), 'Unknown actions must fail closed');
    check(($validate->invoke($groups) === null) === $allowed, "$role mutation guard");
    check($permissions->isValid('save') === $user->admin, "$role permission-rule editor");
}
$user->admin = true;
foreach (array('method', 'context', 'token', 'missing-session-token') as $invalid) {
    $_SERVER['REQUEST_METHOD'] = $invalid === 'method' ? 'GET' : 'POST';
    $groups->params['context'] = $invalid === 'context' ? 'other-course' : 'fixture-course';
    $groups->params['membershiptoken'] = $invalid === 'token' ? 'invalid' : $token;
    $groups->session['membershipToken'] = $invalid === 'missing-session-token' ? '' : $token;
    check($validate->invoke($groups) !== null, "Reject invalid $invalid even for administrator");
}
// Isolate dispatchControl from constructor services and activity logging.
$access = (new ReflectionClass(access::class))->newInstanceWithoutConstructor();
foreach (array('modulesNotToLog' => array(), 'logActivity' => 'FALSE') as $name => $value) {
    (new ReflectionProperty(access::class, $name))->setValue($access, $value);
}
foreach (array(array(true, false, false), array(true, true, true), array(false, false, true)) as [$protected, $valid, $expected]) {
    $module = new class($protected, $valid) {
        public $called = false;
        public function __construct(private $protected, private $valid) {}
        public function requiresLogin($action) { return $this->protected; }
        public function isValid($action) { return $this->valid; }
        public function dispatch($action) { $this->called = true; return 'allowed'; }
    };
    $access->dispatchControl($module, 'fixture');
    check($module->called === $expected, 'Dispatcher must enforce protected policy and preserve public routes');
}
echo "PASS: controller role, revocation, mutation validation and dispatch behaviour\n";
