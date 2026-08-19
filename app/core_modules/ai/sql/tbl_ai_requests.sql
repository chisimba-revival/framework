<?php
/**
 * AI request audit table definition.
 *
 * Stores cross-domain execution metadata only. Prompt and response content are
 * deliberately excluded to minimise retained user data.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$tablename = 'tbl_ai_requests';
$options = array(
    'comment' => 'AI service request audit metadata',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'consumer_module' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'task_name' => array('type' => 'text', 'length' => 96, 'notnull' => TRUE),
    'provider' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'model' => array('type' => 'text', 'length' => 128),
    'success' => array('type' => 'integer', 'length' => 1, 'notnull' => TRUE),
    'input_tokens' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'output_tokens' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'duration_ms' => array('type' => 'integer', 'notnull' => TRUE, 'default' => 0),
    'error_code' => array('type' => 'text', 'length' => 96),
    'date_created' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'ai_request_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'ai_request_consumer_task' => array(
        'fields' => array('consumer_module' => array(), 'task_name' => array())
    ),
    'ai_request_created' => array(
        'fields' => array('date_created' => array())
    )
);
?>
