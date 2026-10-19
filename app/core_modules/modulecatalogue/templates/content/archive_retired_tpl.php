<?php
/** Retired route remains informative without processing uploaded executable code. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('No direct access'); }
echo '<p>' . htmlspecialchars($this->objLanguage->languageText(
    'mod_modulecatalogue_archive_retired', 'modulecatalogue'
), ENT_QUOTES, 'UTF-8') . '</p>';
