<?php

/**
 * Static contract checks for the unified course catalogue.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */

$module = dirname(__DIR__);
$read = function ($relative) use ($module) {
    $path = $module . '/' . $relative;
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException('Unable to read ' . $path);
    }
    return $content;
};
$expect = function ($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$register = $read('register.conf');
$database = $read('classes/dbcontext_class_inc.php');
$renderer = $read('classes/coursecatalogue_class_inc.php');
$block = $read('classes/block_latestcourses_class_inc.php');
$controller = $read('controller.php');

$expect(
    strpos($register, 'WIDEBLOCK: latestcourses') !== false,
    'Latest courses must be registered as a wide block.'
);
$expect(
    strpos($database, 'function getArrayOfLatestContexts') !== false
      && strpos($database, 'ORDER BY datecreated DESC') !== false,
    'Published courses must be queried newest first.'
);
$expect(
    strpos($renderer, "'mod_context_viewcourse'") !== false
      && strpos($renderer, "'mod_context_loginorjoinsite'") !== false
      && strpos($renderer, "'mod_context_applyforcourse'") !== false,
    'All three catalogue actions must be represented.'
);
$expect(
    strpos($renderer, "array('action' => 'apply'") !== false,
    'Private non-members must enter the application-information route.'
);
$expect(
    strpos($renderer, 'renderLatest(6)') === false
      && strpos($block, 'renderLatest(6)') !== false,
    'The block must limit its rendered collection to six courses.'
);
$expect(
    strpos($controller, 'protected function __catalogue()') !== false
      && strpos($controller, 'protected function __apply()') !== false,
    'Catalogue and temporary application actions must both be available.'
);
$expect(
    strpos($register, 'mod_context_applicationscomingsoon') !== false,
    'The temporary application message must be registered for translation.'
);
$expect(
    strpos($renderer, 'getContextLecturers') !== false
      && strpos($renderer, "'mod_context_ledby'") !== false,
    'Course cards must obtain lecturer attribution from context membership.'
);

fwrite(STDOUT, "PASS: unified course catalogue contract\n");

?>
