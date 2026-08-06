<?php
/**
 * Read-only access to Communications delivery-attempt records.
 *
 * @category  Chisimba
 * @package   communications
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die(); }

class communicationattempts extends dbTable
{
    public function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorHandler')
    {
        parent::init(
            $tableName !== null ? $tableName : 'tbl_communications_attempts',
            $pearDb,
            $errorCallback
        );
    }

    public function forMessage($messageId)
    {
        $id = strtolower(trim((string) $messageId));
        if (!preg_match('/^[a-f0-9]{32}$/', $id)) { return array(); }
        return $this->getAll(
            "WHERE outbox_id = '" . $id . "' ORDER BY attempt_number ASC"
        );
    }

    public function latest()
    {
        $rows = $this->getAll('ORDER BY date_created DESC LIMIT 1');
        return isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
    }
}
?>
