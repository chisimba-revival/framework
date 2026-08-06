<?php
/**
 * Database definition for privacy-preserving abuse-protection events.
 *
 * @category  Chisimba
 * @package   abuseprotection
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
$tablename = 'tbl_abuse_events';
$options = array(
    'comment' => 'Privacy-preserving abuse-protection event counters',
    'collate' => 'utf8_general_ci',
    'character_set' => 'utf8'
);
$fields = array(
    'id' => array('type' => 'text', 'length' => 32, 'notnull' => TRUE),
    'action_key' => array('type' => 'text', 'length' => 100, 'notnull' => TRUE),
    'subject_hash' => array('type' => 'text', 'length' => 64, 'notnull' => TRUE),
    'outcome' => array('type' => 'text', 'length' => 16, 'notnull' => TRUE),
    'occurred_at' => array('type' => 'timestamp', 'notnull' => TRUE),
    'expires_at' => array('type' => 'timestamp', 'notnull' => TRUE)
);
$tableIndexes = array(
    'abuse_event_primary' => array(
        'primary' => TRUE,
        'fields' => array('id' => array())
    ),
    'abuse_event_lookup' => array(
        'fields' => array(
            'action_key' => array(),
            'subject_hash' => array(),
            'occurred_at' => array()
        )
    ),
    'abuse_event_expiry' => array(
        'fields' => array('expires_at' => array())
    )
);
?>
