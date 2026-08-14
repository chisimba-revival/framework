<?php

/**
 * Published course catalogue page.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<main class="course-catalogue-page" aria-labelledby="course-catalogue-title">
  <h1 id="course-catalogue-title"><?php echo $escape($catalogueTitle); ?></h1>
  <?php echo $catalogueContent; ?>
</main>
