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
$myCourses = $read('classes/block_mycontexts_class_inc.php');
$allCourses = $read('classes/block_context_class_inc.php');

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
      && strpos($renderer, "'mod_context_buy'") !== false
      && strpos($renderer, "'mod_context_join'") !== false,
    'Catalogue actions must include direct course and membership purchase routes.'
);
$expect(
    strpos($renderer, "'type' => 'notice'") !== false
      && strpos($renderer, 'data-course-application-notice') !== false
      && strpos($renderer, 'window.alert') !== false,
    'Manual admission and unavailable sales must receive an honest notice.'
);
$expect(
    strpos($renderer, "getObject(\n                'paymentcatalogservice', 'payment-service'") !== false
      && strpos($renderer, "'product' => (string) \$product['code']") !== false
      && strpos($renderer, "? 'R' . number_format(\$numericAmount, 2)") !== false,
    'A priced gated course must link directly to its server-owned checkout offer.'
);
$expect(
    strpos($renderer, 'specific course entitlement') === false
      && strpos($renderer, 'Purchasing is not yet available') !== false
      && strpos($renderer, 'This course requires approval before you can join.') !== false,
    'Learners must see a concrete next step rather than internal entitlement language.'
);
$expect(
    strpos($renderer, "array('action' => 'showlogin'), 'security'") === false,
    'Course acquisition must use the canonical site entrance, not legacy showlogin.'
);
$expect(
    strpos($renderer, "\$this->escape(\$action['url'])") === false
      && strpos($renderer, "\$this->escape(\$this->uri(") === false,
    'Internally generated catalogue routes must be HTML encoded exactly once.'
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
$expect(
    strpos($renderer, "\$allowed = \$mappedPolicy === 'public'") !== false,
    'Public catalogue actions must not depend on optional access services.'
);
$expect(
    strpos($myCourses, 'course-shortcuts__list') !== false
      && strpos($allCourses, 'course-shortcuts__list') !== false
      && strpos($myCourses, "new dropdown") === false
      && strpos($allCourses, "new dropdown") === false,
    'My Courses and All Courses must use direct course navigation, not chooser dropdowns.'
);

fwrite(STDOUT, "PASS: unified course catalogue contract\n");

?>
