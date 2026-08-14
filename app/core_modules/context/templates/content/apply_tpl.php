<?php

/**
 * Temporary private-course application information page.
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
<main class="course-application" aria-labelledby="course-application-title">
  <p class="course-application__eyebrow"><?php
      echo $escape($applicationPageTitle);
  ?></p>
  <h1 id="course-application-title"><?php
      echo $escape($applicationCourseTitle);
  ?></h1>
  <p><?php echo $escape($applicationMessage); ?></p>
  <p><a class="course-application__back" href="<?php
      echo $escape($this->uri(array('action' => 'catalogue'), 'context'));
  ?>"><?php echo $escape($applicationBackLabel); ?></a></p>
</main>
