<?php
/** Private module-owned files in the shared File Manager catalogue/storage.
 * Callers supply a trusted policy service, never a request-selected class.
 */
if(empty($GLOBALS['kewl_entry_point_run']))die('No direct access');
class modulefilestore extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorCallback'){parent::init('tbl_files',$pearDb,$errorCallback);}
    private function root()
    {
        $root=realpath((string)$this->getObject('dbsysconfig','sysconfig')->getValue('SECUREFODLER','filemanager'));
        $public=realpath($this->getObject('altconfig','config')->getcontentBasePath());
        if(!$root||!is_dir($root)||($public&&($root===$public||str_starts_with($root,$public.'/'))))throw new RuntimeException('Private file storage unavailable');
        return $root;
    }
    private function policy($module,$class){if(!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D',$module))throw new InvalidArgumentException('Invalid module');return $this->getObject($class,$module);}
    public function storeImage($module,$class,$bytes)
    {
        if(!$this->policy($module,$class)->mayStoreFiles())throw new DomainException('forbidden');
        if(!is_string($bytes)||strlen($bytes)>1000000||strlen($bytes)<10)throw new InvalidArgumentException('Invalid image');
        $info=getimagesizefromstring($bytes);if(!$info||$info['mime']!=='image/jpeg'||$info[0]>2048||$info[1]>2048)throw new InvalidArgumentException('Prepared JPEG required');
        return $this->storeValidated($module, $bytes, 'jpg', 'image/jpeg', 'images');
    }

    /** Bounded passive media for module-owned activities. Never accepts active documents. */
    public function storeMedia($module, $class, $bytes)
    {
        if (!$this->policy($module, $class)->mayStoreFiles()) throw new DomainException('forbidden');
        $type = self::mediaType($bytes);
        return $this->storeValidated($module, $bytes, $type['extension'], $type['mime'], $type['category']);
    }

    public static function mediaType($bytes)
    {
        if (!is_string($bytes) || strlen($bytes) < 10 || strlen($bytes) > 10485760) throw new InvalidArgumentException('Invalid media size');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $types = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'audio/mpeg'=>'mp3'];
        if (!isset($types[$mime])) throw new InvalidArgumentException('Unsupported media');
        if (str_starts_with($mime, 'image/')) {
            $info = getimagesizefromstring($bytes);
            if (!$info || $info['mime'] !== $mime || $info[0] > 8192 || $info[1] > 8192 || $info[0] * $info[1] > 24000000) throw new InvalidArgumentException('Invalid image dimensions');
        }
        return ['mime'=>$mime, 'extension'=>$types[$mime], 'category'=>str_starts_with($mime, 'image/') ? 'images' : 'audio'];
    }

    private function storeValidated($module, $bytes, $extension, $mime, $category)
    {
        $id=bin2hex(random_bytes(16));$folder='moduleassets/'.$module;$relative=$folder.'/'.$id.'.'.$extension;$root=$this->root();
        if(!is_dir($root.'/'.$folder)&&!mkdir($root.'/'.$folder,0700,true)&&!is_dir($root.'/'.$folder))throw new RuntimeException('Storage unavailable');
        $dir=realpath($root.'/'.$folder);if(!$dir||!str_starts_with($dir,$root.'/'))throw new RuntimeException('Invalid storage path');
        $path=$dir.'/'.$id.'.'.$extension;$out=fopen($path,'xb');if(!$out)throw new RuntimeException('Storage unavailable');
        try{if(fwrite($out,$bytes)!==strlen($bytes))throw new RuntimeException('Incomplete write');}catch(Throwable $e){fclose($out);unlink($path);throw $e;}fclose($out);chmod($path,0600);
        try{
            $uid=(string)$this->getObject('user','security')->userId();
            $result=$this->insert(['id'=>$id,'userid'=>$uid,'filename'=>$id.'.'.$extension,'datatype'=>$extension,'path'=>$relative,'filefolder'=>$folder,'version'=>1,'filesize'=>strlen($bytes),'mimetype'=>$mime,'category'=>$category,'moduleuploaded'=>$module,'creatorid'=>$uid,'modifierid'=>$uid,'datecreated'=>gmdate('Y-m-d'),'timecreated'=>gmdate('H:i:s'),'visibility'=>'hidden','access'=>'private_selected']);
            if(!$result)throw new RuntimeException('File registration failed');
        }catch(Throwable $e){unlink($path);throw $e;}
        return $id;
    }
    /** Compensation for a new, unlinked upload; not a public delete endpoint. */
    public function discard($module,$class,$id)
    {
        if(!$this->policy($module,$class)->mayStoreFiles())throw new DomainException('forbidden');
        $file=$this->getRow('id',$id);if(!$file||$file['filefolder']!=='moduleassets/'.$module)throw new DomainException('not_found');
        $path=$this->getObject('filedelivery','filemanager')->resolve($this->root(),$file['path']);
        $this->delete('id',$id);if($path&&!unlink($path))throw new RuntimeException('Unlinked upload cleanup failed');
    }
    public function read($module,$class,$resource,$id)
    {
        return $this->readBounded($module,$class,$resource,$id,1000000);
    }
    /** Read validated passive assessment media without raising the legacy image limit. */
    public function readMedia($module,$class,$resource,$id)
    {
        $bytes=$this->readBounded($module,$class,$resource,$id,10485760);
        self::mediaType($bytes);
        return $bytes;
    }
    private function readBounded($module,$class,$resource,$id,$limit)
    {
        $file=$this->getRow('id',$id);
        if(!$file||($file['filefolder']??'')!=='moduleassets/'.$module||!$this->policy($module,$class)->mayDownloadFile($file,$resource))throw new DomainException('not_found');
        $path=$this->getObject('filedelivery','filemanager')->resolve($this->root(),$file['path']);
        if(!$path||filesize($path)>$limit)throw new DomainException('not_found');
        $bytes=file_get_contents($path);if($bytes===false)throw new RuntimeException('File read failed');return $bytes;
    }
}
