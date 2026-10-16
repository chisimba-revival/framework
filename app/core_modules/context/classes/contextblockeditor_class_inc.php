<?php
/** Native course-home request boundary. Persist through dbcontextblocks only. */
if(empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class contextblockeditor extends ChisimbaObject
{
    public function init() {}
    public function csrf(){return $this->getObject('nativeauthwebcomposition','security')->build()['csrf'];}
    public function purpose($code){return 'context_blocks_'.substr(hash('sha256',$code),0,32);}
    protected function editingScope(array $input) {
        $code=(string)$this->getObject('dbcontext','context')->getContextCode();
        $user=$this->getObject('user','security');
        if (!$user->isLoggedIn() || $code==='' || $code==='root' || ($input['contextcode']??null)!==$code
            || (!$user->isAdmin() && !$this->getObject('managegroups','contextgroups')->isContextLecturer())) return null;
        return $code;
    }
    protected function offeredDynamicBlocks($wide,$code) {
        $dynamic=$this->getObject('dynamicblocks','blocks');
        return $wide?$dynamic->getWideContextBlocks($code):$dynamic->getSmallContextBlocks($code);
    }
    protected function offeredModuleBlocks($wide) {
        return $this->getObject('dbmoduleblocks','modulecatalogue')->getBlocks($wide?'wide':'normal','context|site');
    }
    public function allowed($id,$side,$code): bool {
        $wide=$side==='middle';
        $rows=$this->offeredDynamicBlocks($wide,$code);
        foreach($rows as $row) if($id==='dynamicblock|'.$row['id'].'|'.$row['module'] && $this->getObject('modules','modulecatalogue')->checkIfRegistered($row['module'])) return true;
        foreach($this->offeredModuleBlocks($wide) as $row) {
            if($row['moduleid']!=='contentblocks' && $id==='block|'.$row['blockname'].'|'.$row['moduleid'])return true;
        }
        if($this->getObject('modules','modulecatalogue')->checkIfRegistered('contentblocks')) {
            foreach((array)$this->getObject('dbcontentblocks','contentblocks')->getBlocksArr($wide?'content_widetext':'content_text') as $row) {
                if($id==='block|'.$row['id'].'|contentblocks')return true;
            }
        }
        return false;
    }
    public function handle($action,array $input,$method): array {
        $code=$this->editingScope($input);
        $result=['ok'=>false,'status'=>403,'code'=>'block_denied'];
        if ($code===null) return $result;
        try {
            if($method!=='POST')throw new RuntimeException('block_method');
            if(!is_string($input['csrf_token']??null)||!$this->csrf()->consume($this->purpose($code),$input['csrf_token']))throw new RuntimeException('block_csrf');
            $id=$input['blockid']??null;$side=$input['side']??null;$direction=$input['direction']??null;
            if(!is_string($id)||$id==='')throw new RuntimeException('block_denied');
            if(in_array($action,['addblock','renderblock'],true) && (!in_array($side,['left','middle','right'],true)||!$this->allowed($id,$side,$code)))throw new RuntimeException('block_denied');
            if($action==='moveblock'&&!in_array($direction,['up','down'],true))throw new RuntimeException('block_denied');
            $repo=$this->getObject('dbcontextblocks','context');
            if($action==='renderblock') {
                $parts=explode('|',$id);
                $html=$parts[0]==='block'?$this->getObject('blocks','blocks')->showBlock($parts[1],$parts[2],null,20,true,false):$this->getObject('dynamicblocks','blocks')->showBlock($parts[1]);
                $result=['ok'=>true,'status'=>200,'html'=>'<div class="block">'.$html.'</div>'];
            } else {
                if(!in_array($action,['addblock','removeblock','moveblock'],true)||!is_string($input['revision']??null))throw new RuntimeException('block_denied');
                $revision=$repo->editPlacement($code,$input['revision'],$action,$id,$side,$direction);
                $result=['ok'=>true,'status'=>200,'revision'=>$revision];
            }
        } catch(Throwable $error) {
            $errorCode=$error->getMessage();
            if(!in_array($errorCode,['block_denied','block_stale','block_csrf','block_method'],true))$errorCode='block_uncertain';
            $result=['ok'=>false,'status'=>$errorCode==='block_method'?405:($errorCode==='block_stale'?409:403),'code'=>$errorCode];
        }
        $result['csrf_token']=$this->csrf()->issue($this->purpose($code));
        if(isset($result['code']))$result['message']=html_entity_decode($this->getObject('language','language')->languageText('mod_context_'.$result['code'],'context'),ENT_QUOTES,'UTF-8');
        return $result;
    }
}
