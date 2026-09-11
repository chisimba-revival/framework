<?php
/** Persistence for one reusable biography per logical user. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class dbauthorbiographies extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init('tbl_userdetails_biographies', $pearDb, $errorCallback);
    }

    /** Return only explicitly authored public biography data. */
    public function forUser($userId)
    {
        $row = $this->getRow('userid', (string)$userId);
        return is_array($row) ? $row : null;
    }

    /** Save to a stable per-user key; the database also enforces uniqueness. */
    public function saveForUser($userId, array $value)
    {
        $data = array('biography' => $value['biography'],
            'links_json' => json_encode($value['links'], JSON_THROW_ON_ERROR),
            'updated_at' => gmdate('Y-m-d H:i:s'));
        if ($this->forUser($userId)) return $this->update('userid', (string)$userId, $data);
        $data['id'] = bin2hex(random_bytes(16));
        $data['userid'] = (string)$userId;
        return $this->insert($data);
    }
}
