<?php
/** Minimal role-aware landing seam for authenticated navigation events. */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }

class landingresolver extends ChisimbaObject
{
    public function init()
    {
        $this->user = $this->getObject('user', 'security');
        $this->contexts = $this->getObject('usercontext', 'context');
        $this->modules = $this->getObject('modules', 'modulecatalogue');
    }

    public function defaultModule($fallback)
    {
        return $this->isStudentOnly() && $this->modules->checkIfRegistered('mylearning')
            ? 'mylearning'
            : $fallback;
    }

    public function leaveCourseModule($contextCode, $fallback = '_default')
    {
        return $this->isStudentOnly($contextCode)
            && $this->modules->checkIfRegistered('mylearning')
            ? 'mylearning'
            : $fallback;
    }

    public function isStudentOnly($contextCode = null)
    {
        if (!$this->user->isLoggedIn() || $this->user->isAdmin()) { return false; }
        $userId = $this->user->userId();
        if ($contextCode !== null && $contextCode !== '') {
            return $this->user->isContextStudent($contextCode)
                && !$this->user->isContextLecturer($userId, $contextCode);
        }
        $studentContexts = (array) $this->contexts->getContextWhereStudent($userId);
        $lecturerContexts = (array) $this->contexts->getContextWhereLecturer($userId);
        return $studentContexts !== array()
            && $lecturerContexts === array()
            && !$this->user->isLecturer();
    }
}
?>
