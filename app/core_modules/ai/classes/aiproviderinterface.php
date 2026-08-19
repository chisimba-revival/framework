<?php
/**
 * Provider-neutral AI execution contract.
 *
 * @category  Chisimba
 * @package   ai
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

interface AiProviderInterface
{
    /** Execute one normalised AI request and return a stable provider result array. */
    public function execute(array $request);

    /** Return provider configuration/status information safe for display. */
    public function status();
}
?>
