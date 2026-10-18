<?php
/** Shared transactional ordering for scoped block repositories. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class blockplacementorder extends ChisimbaObject
{
    public function init() {}

    /** The owning repository supplies its scope, reads and trusted insert fields. */
    public function edit($repo, $code, $revision, $action, $id, $side, $direction, array $fields)
    {
        if (!is_string($code) || $code === '' || !is_string($id) || $id === '') throw new RuntimeException('block_denied');
        if ($action === 'addblock' && (!in_array($side, ['left','middle','right'], true) || count(explode('|', $id)) !== 3)) throw new RuntimeException('block_denied');
        if ($action === 'moveblock' && !in_array($direction, ['up','down'], true)) throw new RuntimeException('block_denied');
        return $repo->withDatabaseLock('block-placements', function() use($repo,$code,$revision,$action,$id,$side,$direction,$fields) {
            return $repo->transactionWithSavepoint(function() use($repo,$code,$revision,$action,$id,$side,$direction,$fields) {
                if (!hash_equals($repo->editingRevision($code), $revision)) throw new RuntimeException('block_stale');
                $rows=$repo->editingRows($code);
                if ($action==='addblock') {
                    $position=0;
                    foreach($rows as $row) if($row['side']===$side) $position=max($position,(int)$row['position']);
                    $parts=explode('|',$id);
                    $result=$repo->insert(array_merge($fields, ['block'=>$id,'side'=>$side,'module'=>$parts[2],'position'=>$position+1,'datelastupdated'=>date('Y-m-d H:i:s')]));
                    if (!$result) throw new RuntimeException('block_failed');
                } else {
                    $record=null;
                    foreach($rows as $row) if($row['id']===$id) $record=$row;
                    if (!$record) throw new RuntimeException('block_denied');
                    if ($action==='removeblock') {
                        if (!$repo->delete('id',$id)) throw new RuntimeException('block_failed');
                    } elseif ($action==='moveblock') {
                        $ordered=array_values(array_filter($rows,static fn($row)=>$row['side']===$record['side']));
                        $index=array_search($id,array_column($ordered,'id'),true);
                        $other=$index+($direction==='up'?-1:1);
                        if (!isset($ordered[$other])) throw new RuntimeException('block_stale');
                        [$ordered[$index],$ordered[$other]]=[$ordered[$other],$ordered[$index]];
                        foreach($ordered as $i=>$row) if ($repo->update('id',$row['id'],['position'=>$i+1])===false) throw new RuntimeException('block_failed');
                    } else throw new RuntimeException('block_denied');
                }
                return $repo->editingRevision($code);
            });
        });
    }
}
