<?php
/** Request boundary for native own-profile actions; no caller-supplied user identity. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class profileactions extends ChisimbaObject
{
    public function init() {}
    public function csrf() { return $this->getObject('nativeauthwebcomposition', 'security')->build()['csrf']; }
    public function tokenField(string $purpose): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->csrf()->issue($purpose), ENT_QUOTES, 'UTF-8') . '">';
    }
    public function handle(string $action, array $input, string $method): array
    {
        $user = $this->getObject('user', 'security');
        if (!$user->isLoggedIn()) return ['status'=>403, 'ok'=>false, 'code'=>'profile_denied'];
        $purpose = $action === 'ajaxChangeGrade' ? 'profile_grade' : 'profile_photo';
        $result = ['status'=>200, 'ok'=>false, 'code'=>'profile_failed'];
        if ($method !== 'POST') $result = ['status'=>405, 'ok'=>false, 'code'=>'profile_method'];
        elseif (!is_string($input['csrf_token'] ?? null) || !$this->csrf()->consume($purpose, $input['csrf_token'])) {
            $result = ['status'=>403, 'ok'=>false, 'code'=>'profile_csrf'];
        } else {
            try {
                if ($action === 'ajaxChangeImage') {
                    $id = $input['imageselect'] ?? '';
                    if (!is_string($id) || $id === '') throw new RuntimeException('profile_image_access');
                    $this->getObject('profilephoto', 'userdetails')->change($id);
                } elseif ($action === 'ajaxResetImage') {
                    $this->getObject('profilephoto', 'userdetails')->reset();
                } elseif ($action === 'ajaxChangeGrade') {
                    $this->changeGrade($input['new_name'] ?? null);
                } else throw new RuntimeException('profile_denied');
                $result = ['status'=>200, 'ok'=>true, 'code'=>$action === 'ajaxChangeGrade' ? 'gradesuccess' : ($action === 'ajaxResetImage' ? 'profile_photo_reset' : 'userimagechanged')];
                if ($action !== 'ajaxChangeGrade') $result['image'] = $user->getUserImage(null, true);
            } catch (Throwable $error) {
                $code = $error->getMessage();
                if (!in_array($code, ['profile_denied','profile_image_access','profile_image_invalid','profile_failed','gradeerror'], true)) $code='profile_failed';
                $result = ['status'=>$code==='profile_denied'?403:422, 'ok'=>false, 'code'=>$code];
            }
        }
        $result['csrf_token'] = $this->csrf()->issue($purpose);
        $result['message'] = html_entity_decode($this->getObject('language', 'language')->languageText('mod_userdetails_'.$result['code'], 'userdetails'), ENT_QUOTES, 'UTF-8');
        return $result;
    }
    private function changeGrade($name): void
    {
        $user=$this->getObject('user','security');
        // Canonical group membership writes are administrator-only.
        if (!$user->isAdmin() || !$this->getObject('modules','modulecatalogue')->checkIfRegistered('schoolusers') || !is_string($name)) throw new RuntimeException('profile_denied');
        $grades=$this->getObject('dbgrades','grades');
        $names=array_column($grades->getAll(),'name');
        if (!in_array($name,$names,true)) throw new RuntimeException('gradeerror');
        $groups=$this->getObject('groupservice','groupadmin');
        $target=$groups->groupIdForName($name);
        if ($target===false || $target===null) throw new RuntimeException('gradeerror');
        $grades->transaction(function()use($names,$name,$groups,$target,$user){
            $result=$groups->addMember($target,$user->userId());
            if (empty($result['ok'])) throw new RuntimeException('gradeerror');
            foreach ($names as $old) {
                if ($old===$name) continue;
                $id=$groups->groupIdForName($old);
                if ($id===false || $id===null) continue;
                foreach ($groups->getMembers($id) as $member) {
                    if ((string)$member['userId']!==(string)$user->userId()) continue;
                    $result=$groups->removeMember($id,$user->userId());
                    if (empty($result['ok'])) throw new RuntimeException('gradeerror');
                }
            }
        });
    }
    public function schools(string $term): array
    {
        if (!$this->getObject('user','security')->isLoggedIn() || !$this->getObject('modules','modulecatalogue')->checkIfRegistered('schoolusers') || mb_strlen(trim($term))<3) return [];
        $rows=$this->getObject('dbschools_schools','schools')->autocomplete(mb_substr(trim($term),0,100));
        $out=[];
        foreach (array_slice((array)$rows,0,20) as $row) {
            if (is_array($row) && isset($row['label'],$row['value'])) $out[]=['label'=>(string)$row['label'],'value'=>(string)$row['value']];
        }
        return $out;
    }
}
