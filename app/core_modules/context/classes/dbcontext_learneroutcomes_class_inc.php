<?php
/* ----------- data class extends dbTable for tbl_context_learningoutcomes------------*/
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
/**
 * Model class for the table tbl_context_learningoutcomes
 * @author Paul Mungai
 * @copyright 2010 University of the Witwatersrand
 */
class dbContext_learneroutcomes extends dbTable
{
    /**
     * The user Object
     *
     * @var object $objUser
     */
    public $objDBContext;
    public $objUser;
    /**
     * The user Id
     *
     * @var object $objUserId
     */
    public $objUserId;
    /**
     * Constructor method to define the table
     */
    function init($tableName = null, $pearDb = null, $errorCallback = 'globalPearErrorCallback') 
    {
        parent::init('tbl_context_learneroutcomes');
        $this->objUser = $this->getObject('user', 'security');
        $this->objDBContext = $this->getObject('dbcontext', 'context');
        $this->objUserId = $this->objUser->userId();
    }
    /**
     * Return all records
     * @param string $userid The User ID
     * @return array The entries
     */
    function listAll($userid) 
    {
        return $this->getAll("WHERE userid='" . $userid . "'");
    }
    /**
     * Return all context records
     * @param string $contextcode The Context Code
     * @return array The learning outcomes
     */
    function getContextOutcomes($contextCode)
    {
        return $this->selectPrepared('SELECT * FROM tbl_context_learneroutcomes WHERE contextcode = ? ORDER BY id', [$contextCode]);
    }
    /** Explicit catalogue upgrade: never migrate data during a reader call. */
    public function migrateLegacyGoals()
    {
        require_once __DIR__ . '/../../../classes/core/chisimbadatabaseschema.php';
        $schema = new ChisimbaDatabaseSchema($this);
        $schema->requireTransactional('tbl_context');
        $schema->requireTransactional('tbl_context_learneroutcomes');
        return $this->transactionWithSavepoint(function () {
            $courses = $this->selectPrepared("SELECT contextcode,goals,userid FROM tbl_context WHERE goals IS NOT NULL AND goals NOT IN ('', 'deleted', '0') FOR UPDATE", []);
            foreach ($courses as $course) {
                if (!$this->insertSingle($course['contextcode'], $course['goals'], strlen((string)$course['userid']) <= 25 ? (string)$course['userid'] : '')) throw new RuntimeException('Legacy outcomes migration failed');
                $this->executePrepared("UPDATE tbl_context SET goals = 'deleted' WHERE contextcode = ?", [$course['contextcode']]);
            }
            return count($courses);
        });
    }
    /**
     * Return formated list of context outcomes
     * @param string $contextcode The Context Code
     * @return Ordered list of The learning outcomes
     */
    function listContextOutcomes($contextCode) 
    {
      //Get all LO
      $getLO = $this->getContextOutcomes($contextCode);
      if(!empty($getLO)) {
        $str = "<ul>";
        $count = 1;
        foreach($getLO as $thisLO){
          $str .= "<li>".$count.". ".$thisLO["learningoutcome"]."</li>";
          $count = $count + 1;
        }
        $str .= "</ul>";
      } else {
        $str = " ";
      }
      return $str;
    }
    /**
     * Return a single record
     * @param string $id ID
     * @return array The values
     */
    function listSingle($id) 
    {
        return $this->getAll("WHERE id='" . $id . "'");
    }
    /**
     * Insert a record
     * @param string $contextcode The Context Code
     * @param string $learningoutcome The Learning Outcome
     */
    function insertSingle($contextCode, $learningoutcome, $actor = null)
    {
        //Insert Data
        $id = $this->insert(array(
            'contextcode' => $contextCode,
            'learningoutcome' => $learningoutcome,
            'createdby' => ($actor ?? (string)$this->objUserId) ?: 'system',
            'createdon' => $this->now(),
        ));
        return $id;
    }
    /**
     * Update a record
     * @param string $id The record Id
     * @param string $learningoutcome The Learning Outcome
     */
    function updateSingle($id, $learningoutcome) 
    {
        $this->update("id", $id, array(
            'learningoutcome' => $learningoutcome,
        ));
    }
    /**
     * Delete a record
     * @param string $id ID
     */
    function deleteSingle($id) 
    {
        $this->delete("id", $id);
    }
}
?>
