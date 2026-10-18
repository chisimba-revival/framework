<?php
/** Shared record-based read boundary for File Manager. @author Derek Keats */
class filereadpolicy extends ChisimbaObject
{
    public function mayRead($file)
    {
        if (!is_array($file) || empty($file['id']) || empty($file['filefolder'])) return false;
        if (str_starts_with((string)$file['filefolder'], 'assignment/')) return false;
        $user = $this->getObject('user', 'security');
        $uid = $user->isLoggedIn() ? (string)$user->userId() : '';
        $admin = $uid !== '' && $user->inAdminGroup($uid, 'Site Admin');
        // Submission files are served only through Assignment's submission guard.
        // Never let a generic file identifier bypass that stronger boundary.
        if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('assignment')) {
            $submissions = $this->getObject('dbassignmentsubmit', 'assignment');
            if ($submissions->getRow('studentfileid', $file['id'])
                || $submissions->getRow('lecturerfileid', $file['id'])) return false;
        }
        $parts = explode('/', trim((string)$file['filefolder'], '/'));
        $folder = $this->getObject('dbfolder','filemanager');
        $record = $folder->getFolder($folder->getFolderId($file['filefolder']));
        if (!is_array($record)) return false;
        $course = null; $member = false; $teacher = false;
        if ($parts[0] === 'context') {
            if (empty($parts[1])) return false;
            $course = $this->getObject('dbcontext','context')->getContext($parts[1]);
            if (!is_array($course) || !$course) return false;
            if ($uid !== '') {
                $member = $this->getObject('usercontext','context')->isContextMember($uid,$parts[1]);
                $teacher = $user->isContextLecturer($uid,$parts[1]);
            }
        }
        return self::allows($file,$record,$course,$uid,$admin,$member,$teacher);
    }

    public static function allows(array $file,array $folder,$course,$uid,$admin,$member,$teacher)
    {
        if ($admin) return true;
        $owner = $uid !== '' && $uid === (string)($file['userid'] ?? $file['creatorid'] ?? '');
        $hidden = ($file['visibility'] ?? '') === 'hidden';
        $selected = in_array('private_selected',[$file['access'] ?? '',$folder['access'] ?? ''],true);
        if ($hidden || $selected) return ($owner && ($course === null || $member || $teacher))
            || ($course !== null && $teacher);
        if ($course !== null) {
            if ($member || $teacher) return true;
            return strtolower((string)($course['access'] ?? '')) === 'public'
                && strtolower((string)($course['status'] ?? '')) === 'published'
                && ($file['access'] ?? 'public') === 'public'
                && ($folder['access'] ?? 'public') === 'public';
        }
        if (in_array('private_all',[$file['access'] ?? '',$folder['access'] ?? ''],true)) return $uid !== '';
        return true;
    }
}
