<?php
/** Public biography application service. No account data is copied into biographies. @author Derek Keats */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class authorbiographyservice extends ChisimbaObject
{
    private $store;
    private $user;

    public function init()
    {
        $this->store = $this->getObject('dbauthorbiographies', 'userdetails');
        $this->user = $this->getObject('user', 'security');
        $this->loadClass('authorbiographyvalue', 'userdetails');
    }

    /** The current authenticated identity is the only editable biography. */
    public function saveOwn(array $input)
    {
        if (!$this->user->isLoggedIn()) throw new RuntimeException('Authentication required');
        $result = authorbiographyvalue::validate($input);
        if (!$result['errors']) {
            $saved = $this->store->saveForUser($this->user->userId(), $result['value']);
            if ($saved === false) throw new RuntimeException('Biography persistence failed');
        }
        return $result;
    }

    /** Read reusable public fields only. */
    public function forUser($userId)
    {
        $row = $this->store->forUser($userId);
        return array('biography' => (string)($row['biography'] ?? ''),
            'links' => $row ? (json_decode($row['links_json'], true) ?: array()) : array());
    }

    /** Existing lecturer assignments remain authoritative; no parallel author membership table. */
    public function forCourse($contextCode)
    {
        $rows = $this->getObject('usercontext', 'context')->getContextLecturers($contextCode);
        $authors = array();
        foreach ((array)$rows as $row) {
            $id = (string)($row['userid'] ?? $row['userId'] ?? '');
            if ($id === '' || isset($authors[$id])) continue;
            $authors[$id] = array_merge($this->forUser($id), array('userid' => $id,
                'name' => trim($row['firstname'].' '.$row['surname'])));
        }
        uasort($authors, static function ($a, $b) { return strcasecmp($a['name'], $b['name']) ?: strcmp($a['userid'], $b['userid']); });
        return array_values($authors);
    }
}
