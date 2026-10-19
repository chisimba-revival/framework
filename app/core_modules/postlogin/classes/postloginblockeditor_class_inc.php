<?php
/** Site-home policy over the shared scoped block editor and ordering service. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
$this->loadClass('contextblockeditor', 'context');
class postloginblockeditor extends contextblockeditor
{
    public function canEdit(): bool {
        $user=$this->getObject('user','security');
        return $user->isLoggedIn() && $user->lookupAdmin($user->userId());
    }
    public function purpose($code) { return 'postlogin_blocks_root'; }
    protected function editingScope(array $input) {
        return $this->canEdit() && ($input['contextcode']??null)==='root' ? 'root' : null;
    }
    protected function offeredDynamicBlocks($wide,$code) {
        $dynamic=$this->getObject('dynamicblocks','blocks');
        return $wide?$dynamic->getWideSiteBlocks():$dynamic->getSmallSiteBlocks();
    }
    protected function offeredModuleBlocks($wide) {
        $catalogue=$this->getObject('modules','modulecatalogue');
        return array_filter($this->getObject('dbmoduleblocks','modulecatalogue')->getBlocks($wide?'wide':'normal','site|postlogin'),
            static fn($row)=>$catalogue->checkIfRegistered($row['moduleid']));
    }
}
