<?php
/** Publishes a bounded public presentation of a course. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class coursemarketingservice extends ChisimbaObject
{
    private $store;
    private $user;
    public function init()
    {
        $this->store=$this->getObject('dbcoursemarketing','context');
        $this->user=$this->getObject('user','security');
    }
    public function canManage($code)
    {
        return $this->user->isLoggedIn() && ($this->user->isAdmin() || $this->user->isContextLecturer($this->user->userId(),$code));
    }
    public function page(array $course)
    {
        $row=$this->store->forCourse($course['contextcode']);
        $defaults=array('introduction'=>'','audience'=>'','outcomes'=>'','outline'=>'','video_url'=>'');
        return array('content'=>array_merge($defaults,$row ? (json_decode($row['content_json'],true) ?: array()) : array()),
            'published'=>$row && (int)$row['published']===1 && strtolower($course['status'] ?? '')==='published');
    }
    public function save(array $course,array $input)
    {
        if (!$this->canManage($course['contextcode'])) throw new RuntimeException('Course author permission required');
        $content=array(); $errors=array();
        foreach (array('introduction'=>3000,'audience'=>3000,'outcomes'=>6000,'outline'=>6000,'video_url'=>2048) as $key=>$limit) {
            $content[$key]=is_string($input[$key] ?? null) ? trim($input[$key]) : '';
            if (mb_strlen($content[$key])>$limit) $errors[]='marketing_invalid';
        }
        if ($content['video_url']!=='' && self::videoEmbed($content['video_url'])===null) $errors[]='marketing_video_invalid';
        $published=($input['published'] ?? '')==='1';
        if ($published && $content['introduction']==='') $errors[]='marketing_intro_required';
        if (!$errors && $this->store->savePage($course['contextcode'],$content,$published)===false) throw new RuntimeException('Marketing page save failed');
        return array('content'=>$content,'published'=>$published,'errors'=>array_unique($errors));
    }
    /** Only known public video hosts are embedded; no arbitrary iframe HTML. */
    public static function videoEmbed($url)
    {
        $p=parse_url($url);
        if (!$p || strtolower($p['scheme'] ?? '')!=='https' || isset($p['user']) || isset($p['pass']) || isset($p['port'])) return null;
        $host=strtolower($p['host'] ?? ''); $id='';
        if ($host==='youtu.be') $id=trim($p['path'] ?? '', '/');
        elseif (in_array($host,array('youtube.com','www.youtube.com','m.youtube.com'),true)) {
            if (($p['path'] ?? '')==='/watch') { parse_str($p['query'] ?? '',$query); $id=$query['v'] ?? ''; }
            elseif (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})$~',$p['path'] ?? '',$m)) $id=$m[1];
        }
        if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/',$id)) return 'https://www.youtube-nocookie.com/embed/'.$id;
        if (in_array($host,array('vimeo.com','www.vimeo.com'),true) && preg_match('~^/([0-9]+)$~',$p['path'] ?? '',$m)) return 'https://player.vimeo.com/video/'.$m[1];
        return null;
    }
}
