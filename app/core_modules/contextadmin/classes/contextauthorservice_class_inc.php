<?php
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class contextauthorservice extends ChisimbaObject
{
    private $context; private $groups; private $identity; private $user;
    public function init() {
        $this->context = $this->getObject('dbcontext', 'context');
        $this->groups = $this->getObject('groupservice', 'groupadmin');
        $this->identity = $this->getObject('identityservice', 'security');
        $this->user = $this->getObject('user', 'security');
    }
    public function canCreateContext($actorUserId) {
        if (!$this->isCurrentActor($actorUserId)) return false;
        if ($this->user->isAdmin()) return true;
        $groupId = $this->groups->groupIdForName('Lecturers');
        return $groupId !== false && $this->groups->isGroupMember($actorUserId, $groupId);
    }
    public function isAuthor($contextCode, $userId) {
        $groupId = $this->contextAuthorGroupId($contextCode);
        return $groupId !== false && $this->groups->isGroupMember($userId, $groupId);
    }
    public function canManageContext($contextCode, $actorUserId) {
        return $this->isCurrentActor($actorUserId)
            && ($this->user->isAdmin() || $this->isAuthor($contextCode, $actorUserId));
    }
    public function canManageAuthorRoster($contextCode, $actorUserId) {
        if (!$this->isCurrentActor($actorUserId)) return false;
        if ($this->user->isAdmin()) return true;
        $context = $this->context->getContext($contextCode);
        return is_array($context) && isset($context['userid'])
            && (string) $context['userid'] === (string) $actorUserId;
    }
    public function snapshot($contextCode, $actorUserId) {
        $context = $this->context->getContext($contextCode);
        if (!is_array($context) || !$this->canManageContext($contextCode, $actorUserId)) return $this->result(false, 'forbidden');
        $cg = $this->contextAuthorGroupId($contextCode);
        $sg = $this->groups->groupIdForName('Lecturers');
        if ($cg === false || $sg === false) return $this->result(false, 'author_group_missing');
        $authors = $this->groups->getMembers($cg);
        $siteAuthors = $this->groups->getMembers($sg);
        $current = array();
        foreach ($authors as $a) if (isset($a['userId'])) $current[(string) $a['userId']] = true;
        $available = array();
        foreach ($siteAuthors as $a) if (isset($a['userId']) && !isset($current[(string) $a['userId']])) $available[] = $a;
        return array('ok'=>true,'code'=>'ready','context'=>$context,
            'ownerUserId'=>isset($context['userid'])?(string)$context['userid']:'',
            'authors'=>$authors,'availableAuthors'=>$available,
            'canManageRoster'=>$this->canManageAuthorRoster($contextCode,$actorUserId));
    }
    public function addAuthor($contextCode,$targetUserId,$actorUserId) {
        if (!$this->canManageAuthorRoster($contextCode,$actorUserId)) return $this->result(false,'forbidden');
        $sg=$this->groups->groupIdForName('Lecturers'); $cg=$this->contextAuthorGroupId($contextCode);
        if ($sg===false || $cg===false) return $this->result(false,'author_group_missing');
        if (!$this->groups->isGroupMember($targetUserId,$sg)) return $this->result(false,'not_site_author');
        if ($this->groups->isGroupMember($targetUserId,$cg)) return $this->result(false,'already_author');
        $p=$this->identity->permissionUserIdForUser($targetUserId);
        if (!is_int($p) || $p<1) return $this->result(false,'identity_missing');
        return $this->groups->ensureMembership($cg,$p) ? $this->result(true,'author_added') : $this->result(false,'author_add_failed');
    }
    public function removeAuthor($contextCode,$targetUserId,$actorUserId) {
        if (!$this->canManageAuthorRoster($contextCode,$actorUserId)) return $this->result(false,'forbidden');
        $context=$this->context->getContext($contextCode);
        if (!is_array($context)) return $this->result(false,'context_missing');
        if (isset($context['userid']) && (string)$context['userid']===(string)$targetUserId) return $this->result(false,'owner_must_transfer');
        $cg=$this->contextAuthorGroupId($contextCode);
        if ($cg===false) return $this->result(false,'author_group_missing');
        $authors=$this->groups->getMembers($cg);
        if (count($authors)<=1) return $this->result(false,'cannot_remove_last_author');
        if (!$this->groups->isGroupMember($targetUserId,$cg)) return $this->result(false,'not_author');
        $p=$this->identity->permissionUserIdForUser($targetUserId);
        if (!is_int($p) || $p<1) return $this->result(false,'identity_missing');
        return $this->groups->removeMembership($cg,$p) ? $this->result(true,'author_removed') : $this->result(false,'author_remove_failed');
    }
    public function transferOwnership($contextCode,$targetUserId,$actorUserId) {
        if (!$this->canManageAuthorRoster($contextCode,$actorUserId)) return $this->result(false,'forbidden');
        $context=$this->context->getContext($contextCode);
        if (!is_array($context)) return $this->result(false,'context_missing');
        $cg=$this->contextAuthorGroupId($contextCode);
        if ($cg===false || !$this->groups->isGroupMember($targetUserId,$cg)) return $this->result(false,'new_owner_must_be_author');
        if (isset($context['userid']) && (string)$context['userid']===(string)$targetUserId) return $this->result(true,'owner_unchanged');
        $ok=$this->context->update('contextcode',$contextCode,array('userid'=>$targetUserId,'lastupdatedby'=>$actorUserId,'updated'=>date('Y-m-d H:i:s')));
        if ($ok===false) return $this->result(false,'ownership_transfer_failed');
        $verify=$this->context->getContext($contextCode);
        return is_array($verify) && isset($verify['userid']) && (string)$verify['userid']===(string)$targetUserId
            ? $this->result(true,'ownership_transferred') : $this->result(false,'ownership_transfer_failed');
    }
    private function contextAuthorGroupId($contextCode) {
        $contextCode=trim((string)$contextCode);
        return $contextCode==='' ? false : $this->groups->groupIdForName($contextCode.'^Lecturers');
    }
    private function isCurrentActor($actorUserId) {
        return $this->user->isLoggedIn() && (string)$this->user->userId()===(string)$actorUserId;
    }
    private function result($ok,$code) { return array('ok'=>(bool)$ok,'code'=>(string)$code); }
}
?>
