<?php
/** Private immutable raster storage for owning services. Callers must authorise every read.
 * No file catalogue/public URL is created: generic course-file access must not widen access.
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class privateimageasset extends ChisimbaObject
{
    public function init() {}
    /** Validate actual pixels, then re-encode to discard all embedded metadata. */
    public function prepare($encoded) {
        if(!is_string($encoded)||strlen($encoded)>1000000)throw new DomainException('invalid');
        $bytes=base64_decode($encoded,true);
        if($bytes===false||strlen($bytes)>750000||strlen($bytes)<4)throw new DomainException('invalid');
        set_error_handler(static function($severity,$message){throw new DomainException('invalid');});
        try{
            $info=getimagesizefromstring($bytes);
            if(!$info||$info[2]!==IMAGETYPE_JPEG||$info[0]<1||$info[1]<1||max($info[0],$info[1])>2048)throw new DomainException('invalid');
            $image=imagecreatefromstring($bytes);
            if(!$image)throw new DomainException('invalid');
        }finally{restore_error_handler();}
        try {ob_start();try{if(!imagejpeg($image,null,85))throw new RuntimeException('Image encoding failed');$clean=ob_get_contents();}finally{ob_end_clean();}}
        finally{$image=null;}
        if(strlen($clean)>1500000)throw new DomainException('invalid');
        return ['bytes'=>$clean,'width'=>$info[0],'height'=>$info[1],'digest'=>hash('sha256',$bytes)];
    }
    private function ownerDirectory($owner) {
        if(!is_string($owner)||!preg_match('/^[a-z][a-z0-9_]{1,31}$/D',$owner))throw new InvalidArgumentException('Invalid asset namespace');
        $root=realpath((string)$this->getObject('dbsysconfig','sysconfig')->getValue('SECUREFODLER','filemanager'));
        $public=realpath($this->getObject('altconfig','config')->getsiteRootPath());
        if(!$root||!is_dir($root)||!is_writable($root)||($public&&($root===$public||str_starts_with($root,$public.DIRECTORY_SEPARATOR))))throw new RuntimeException('Private storage is unavailable');
        $path=$root.'/owned-images/'.$owner;
        foreach([$root.'/owned-images',$path] as $dir){
            if(is_link($dir))throw new RuntimeException('Unsafe private storage');
            if(!is_dir($dir)&&!mkdir($dir,0700)&&!is_dir($dir))throw new RuntimeException('Private storage could not be prepared');
        }
        return $path;
    }
    private function directory($owner,$scope) {
        if(!is_string($scope)||!preg_match('/^[a-f0-9]{64}$/D',$scope))throw new InvalidArgumentException('Invalid asset scope');
        $path=$this->ownerDirectory($owner).'/'.$scope;
        if(is_link($path))throw new RuntimeException('Unsafe private storage');
        if(!is_dir($path)&&!mkdir($path,0700)&&!is_dir($path))throw new RuntimeException('Private storage could not be prepared');
        return $path;
    }
    /** Bounded maintenance for files left by a process crash before database commit.
     * The owning service supplies a fresh reference check; never prune recent writes.
     */
    public function pruneUnreferenced($owner,callable $referenced,$apply=false) {
        $count=0;$root=$this->ownerDirectory($owner);
        foreach(new DirectoryIterator($root) as $directory){
            if($directory->isDot()||$directory->isLink()||!$directory->isDir()||!preg_match('/^[a-f0-9]{64}$/D',$directory->getFilename()))continue;
            foreach(new DirectoryIterator($directory->getPathname()) as $file){
                if($file->isDot()||$file->isLink()||!$file->isFile()||!preg_match('/^([a-f0-9]{32})\.jpg$/D',$file->getFilename(),$match)||$file->getMTime()>time()-86400)continue;
                if($referenced($match[1]))continue;
                if($apply&&!unlink($file->getPathname()))throw new RuntimeException('Private orphan cleanup failed');
                if(++$count>=100)return $count;
            }
        }
        return $count;
    }
    private function path($owner,$scope,$id) {
        if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new InvalidArgumentException('Invalid asset ID');
        return $this->directory($owner,$scope).'/'.$id.'.jpg';
    }
    /** Exclusive writes make failed/repeated calls incapable of replacing another image. */
    public function put($owner,$scope,$id,$bytes) {
        $path=$this->path($owner,$scope,$id);$handle=fopen($path,'xb');
        if(!$handle)throw new RuntimeException('Private image could not be created');
        try{chmod($path,0600);$offset=0;while($offset<strlen($bytes)){$written=fwrite($handle,substr($bytes,$offset));if(!$written)throw new RuntimeException('Private image write failed');$offset+=$written;}if(!fflush($handle))throw new RuntimeException('Private image flush failed');}
        catch(Throwable $error){fclose($handle);unlink($path);throw $error;}
        fclose($handle);
    }
    public function read($owner,$scope,$id) {
        $path=$this->path($owner,$scope,$id);
        if(is_link($path)||!is_file($path)||filesize($path)>1500000)throw new DomainException('unavailable');
        $bytes=file_get_contents($path);if($bytes===false)throw new RuntimeException('Private image read failed');return $bytes;
    }
    public function remove($owner,$scope,$id) {
        $path=$this->path($owner,$scope,$id);
        if(is_link($path))throw new RuntimeException('Unsafe private image');
        if(is_file($path)&&!unlink($path))throw new RuntimeException('Private image cleanup failed');
    }
}
