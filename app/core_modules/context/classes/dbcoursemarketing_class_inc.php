<?php
/** Course marketing persistence; no course learning content is copied implicitly. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbcoursemarketing extends dbTable
{
    public function init($tableName=null,$pearDb=null,$errorCallback='globalPearErrorHandler')
    {
        parent::init('tbl_context_marketing',$pearDb,$errorCallback);
    }
    public function forCourse($code)
    {
        $row=$this->getRow('contextcode',(string)$code);
        return is_array($row) ? $row : null;
    }
    public function savePage($code,array $content,$published)
    {
        $values=array('content_json'=>json_encode($content,JSON_THROW_ON_ERROR),'published'=>$published ? 1 : 0,'updated_at'=>gmdate('Y-m-d H:i:s'));
        if ($this->forCourse($code)) return $this->update('contextcode',(string)$code,$values);
        return $this->insert(array_merge($values,array('id'=>bin2hex(random_bytes(16)),'contextcode'=>(string)$code)));
    }
}
