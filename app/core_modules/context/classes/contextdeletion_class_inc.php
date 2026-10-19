<?php
/** Checked context deletion shared by web administration and maintenance.
 * Module-owned cleanup participates in the same transaction. @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class contextdeletion extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback') {
        parent::init('tbl_context',$pearDb,$errorCallback);
    }
    /** Repair is explicit, administrator-only, and never deletes an existing course. */
    public function remove($code, $repair=false) {
        if (!is_string($code) || $code==='' || strlen($code)>32 || str_contains($code,'^')) throw new DomainException('invalid');
        $user=$this->getObject('user','security');
        if (!$user->isLoggedIn() || ($repair ? !$user->isAdmin() : !$this->getObject('contextauthorservice','contextadmin')->canManageAuthorRoster($code,$user->userId()))) throw new DomainException('forbidden');
        $participants=[];
        $catalogue=$this->getObject('modules','modulecatalogue');
        foreach (['messaging'=>'conversationstore','announcements'=>'dbannouncements'] as $module=>$service) {
            if ($catalogue->checkIfRegistered($module)) $participants[]=$this->getObject($service,$module);
        }
        $tables=['tbl_context','tbl_contextmodules','tbl_context_blocks','tbl_perms_groups','tbl_perms_groupusers','tbl_perms_grouprights','tbl_perms_group_subgroups'];
        foreach ($participants as $participant) $tables=array_merge($tables,$participant->contextDeletionTables());
        // A legacy non-transactional installation must fail before touching data.
        foreach ($tables as $table) {
            $row=$this->selectPrepared('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table])[0]??null;
            if (!$row || strcasecmp($row['engine'],'InnoDB')!==0) throw new RuntimeException('Context deletion requires transactional tables: '.$table);
        }
        $documents=['context_contextcode_'.$code];
        $result=$this->transactionWithSavepoint(function()use($code,$repair,$participants,$user,&$documents){
            $row=$this->selectPrepared('SELECT contextcode FROM tbl_context WHERE contextcode=? FOR UPDATE',[$code]);
            if ($repair && $row) throw new DomainException('existing_context');
            if (!$repair && !$row) return false;
            if (!$repair && !$this->getObject('contextauthorservice','contextadmin')->canManageAuthorRoster($code,$user->userId())) throw new DomainException('forbidden');
            foreach ($participants as $participant) {
                $documents=array_merge($documents,$participant->deleteForContext($code));
            }
            if ($this->getObject('groupservice','groupadmin')->deleteContextGroups($code)!==true) throw new RuntimeException('Context group cleanup failed');
            $this->executePrepared('DELETE FROM tbl_contextmodules WHERE contextcode=?',[$code]);
            $this->executePrepared('DELETE FROM tbl_context_blocks WHERE contextcode=?',[$code]);
            $this->executePrepared('DELETE FROM tbl_context WHERE contextcode=?',[$code]);
            return true;
        });
        // Like context creation, indexing belongs after the outermost commit.
        // Nested callers own index maintenance after their transaction completes.
        if ($result && empty($this->selectPrepared('SELECT @@in_transaction AS active')[0]['active'])) {
            try {
                $index=$this->getObject('indexdata','search');
                foreach ($documents as $document) $index->removeIndex($document);
            } catch (Throwable $error) {
                error_log('Context deleted; search index refresh required for '.$code);
            }
        }
        return $result;
    }
}

