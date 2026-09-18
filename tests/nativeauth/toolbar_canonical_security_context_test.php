<?php
/** Exercise the real toolbar security context through its service boundary. */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class ChisimbaObject
{
    public $services = array();
    public function getObject($name, $module = null)
    {
        $key = $module . '/' . $name;
        if (!array_key_exists($key, $this->services)) {
            throw new RuntimeException('Unexpected dependency: ' . $key);
        }
        return $this->services[$key];
    }
    public function appendArrayVar($name,$value) {}
    public function getResourceUri($name,$module) { return '/resources/'.$name; }
    public function uri($params, $module)
    {
        check(in_array($params, array(array('action'=>'logout'),array('action'=>'formtoken')), true) && $module === 'security',
            'logout uses the security endpoint');
        return '/index.php?module=security&amp;action='.$params['action'];
    }
}
function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
class ToolbarUserFixture
{
    public $id = null;
    public $administrator = false;
    public $teaching = array();
    public function isLoggedIn() { return $this->id !== null; }
    public function userId() { return $this->id; }
    public function userName() { return 'teacher-login'; }
    public function fullname() { return 'Test Teacher'; }
    public function inAdminGroup($id, $group)
    {
        check($id === $this->id && $group === 'Site Admin', 'administrator identity and group');
        return $this->administrator;
    }
    public function isContextLecturer($id, $context)
    {
        check($id === $this->id, 'lecturer identity');
        return in_array($context, $this->teaching, true);
    }
    public function isLecturer() { return count($this->teaching) > 0; }
}
require ($argv[1] ?? dirname(__DIR__, 2) . '/app')
    . '/core_modules/toolbar/classes/toolbarsecuritycontext_class_inc.php';
$user = new ToolbarUserFixture();
$csrf = new class {
    public $calls = 0;
    public function issueForSession($purpose) {
        check($purpose === 'native_auth_logout', 'logout token is purpose bound');
        ++$this->calls;
        return 'token"<&';
    }
};
$composition = new class($csrf) {
    private $csrf;
    public function __construct($csrf) { $this->csrf = $csrf; }
    public function build() { return array('csrf' => $this->csrf); }
};
$permissions = new class {
    public $calls = array();
    public function isGranted($user, $right) {
        $this->calls[] = array($user, $right);
        return $user === 'teacher' && $right === 7;
    }
};
$context = new class {
    public $code = null;
    public function getContextCode() { return $this->code; }
};
$learning = new class {
    public $courses = array();
    public function getContextWhereStudent($user) {
        check($user === 'teacher', 'learning identity');
        return $this->courses;
    }
};
$toolbar = new toolbarsecuritycontext();
$toolbar->services = array(
    'security/nativeauthwebcomposition' => $composition,
    'security/user' => $user,
    'security/permissionservice' => $permissions,
    'context/dbcontext' => $context,
    'context/usercontext' => $learning,
    'language/language' => new class { public function languageText($key,$module) { return 'Try again'; } },
);
$toolbar->init();
check(!$toolbar->isAuthenticated() && $toolbar->userId() === null, 'anonymous identity');
check($toolbar->displayName() === '' && $toolbar->userName() === '', 'anonymous display name');
check(!$toolbar->isSiteAdministrator() && !$toolbar->isCurrentContextLecturer()
    && !$toolbar->isLecturer() && !$toolbar->hasStudentLearning(), 'anonymous has no role navigation');
check($toolbar->mayUseRight(null) && $toolbar->mayUseRight(''), 'explicitly public navigation');
check(!$toolbar->mayUseRight(7) && $permissions->calls === array(), 'anonymous named rights fail closed');
check($toolbar->logoutForm('Logout') === '' && $csrf->calls === 0, 'anonymous has no logout form');
$user->id = 'teacher';
check($toolbar->isAuthenticated() && $toolbar->userId() === 'teacher'
    && $toolbar->displayName() === 'Test Teacher'
    && $toolbar->userName() === 'teacher-login', 'authenticated identity');
check(!$toolbar->isSiteAdministrator(), 'ordinary account is not administrator');
$user->administrator = true;
check($toolbar->isSiteAdministrator(), 'administrator membership is honoured');
$user->administrator = false;
check(!$toolbar->isSiteAdministrator(), 'administrator membership is not cached');
$user->teaching = array('grasses');
$context->code = 'grasses';
check($toolbar->isLecturer() && $toolbar->isCurrentContextLecturer(), 'lecturer in own course');
$context->code = 'trees';
check(!$toolbar->isCurrentContextLecturer(), 'lecturer role does not leak across courses');
foreach (array(null, '') as $code) {
    $context->code = $code;
    check(!$toolbar->isCurrentContextLecturer(), 'no current course means no course lecturer');
}
check(!$toolbar->hasStudentLearning(), 'no student enrolments');
$learning->courses = array('birds');
check($toolbar->hasStudentLearning(), 'student journey follows enrolments');
check($toolbar->mayUseRight(7) && !$toolbar->mayUseRight(8), 'grant and denial are honoured');
check($permissions->calls === array(array('teacher', 7), array('teacher', 8)), 'rights use current identity');
$form = $toolbar->logoutForm('Log out <now>', 'toolbar" onmouseover="bad');
check(str_contains($form, 'method="post"'), 'logout uses POST');
check(str_contains($form, 'name="native_auth_logout" value="token&quot;&lt;&amp;"'), 'logout token escaped');
check(str_contains($form, 'Log out &lt;now&gt;') && !str_contains($form, ' onmouseover="'), 'label and class escaped');
check(str_contains($form, 'module=security&amp;action=logout'), 'logout action escaped');
check($csrf->calls === 1, 'logout obtains a session token');
$user->id = null;
check(!$toolbar->isAuthenticated() && !$toolbar->mayUseRight(7)
    && $toolbar->logoutForm('Logout') === '', 'session ending immediately removes access');
echo "Toolbar security context behaviour passed (strict PHP diagnostics).\n";
