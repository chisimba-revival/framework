<?php

/**
 * Course catalogue renderer.
 *
 * Builds semantic course cards for blocks and catalogue pages while leaving
 * colour, spacing, typography and responsive layout to the active skin.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @version   Release: @package_version@
 * @link      https://github.com/chisimba-revival/framework
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Course catalogue renderer.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats <derek@dkeats.com>
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 */
class coursecatalogue extends ChisimbaObject
{
    /** @var object Context database service. */
    public $objContext;

    /** @var object Context image service. */
    public $objContextImage;

    /** @var object Context membership service. */
    public $objUserContext;

    /** @var object Current-user service. */
    public $objUser;

    /** @var object Language service. */
    public $objLanguage;

    /** @var array Context codes available to the current user. */
    private $userContexts = array();

    /**
     * Load course, image, user and language services.
     *
     * @return void
     * @access public
     */
    public function init()
    {
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objContextImage = $this->getObject('contextimage', 'context');
        $this->objUserContext = $this->getObject('usercontext', 'context');
        $this->objUser = $this->getObject('user', 'security');
        $this->objLanguage = $this->getObject('language', 'language');
        $this->userContexts = $this->objUser->isLoggedIn()
            ? $this->objUserContext->getUserContext($this->objUser->userId())
            : array();
    }

    /**
     * Render the newest published courses for the front-page block.
     *
     * One additional row is fetched only to decide whether the catalogue link
     * is required.
     *
     * @param integer $limit Maximum number of cards to show
     * @return string Semantic course-card collection
     * @access public
     */
    public function renderLatest($limit=6)
    {
        $limit = max(1, (int) $limit);
        $contexts = $this->objContext->getArrayOfLatestContexts($limit + 1);
        $hasMore = count($contexts) > $limit;
        if ($hasMore) {
            $contexts = array_slice($contexts, 0, $limit);
        }
        return $this->render($contexts, $hasMore);
    }

    /**
     * Render the complete published course catalogue.
     *
     * @param integer $limit Maximum number of cards to show
     * @return string Semantic course-card collection
     * @access public
     */
    public function renderCatalogue($limit=60)
    {
        $limit = max(1, (int) $limit);
        return $this->render(
            $this->objContext->getArrayOfLatestContexts($limit),
            false
        );
    }

    /**
     * Render a supplied collection of context records as course cards.
     *
     * @param array   $contexts Course records
     * @param boolean $showMore Whether to show the catalogue link
     * @return string Semantic course-card collection
     * @access public
     */
    public function render(array $contexts, $showMore=false)
    {
        if (count($contexts) === 0) {
            return '<p class="course-catalogue__empty">'
              . $this->escape($this->text(
                  'mod_context_coursecatalogueempty',
                  'No courses are available yet.'
              )) . '</p>';
        }

        $output = '<div class="course-card-grid" role="list">';
        foreach ($contexts as $context) {
            $output .= $this->renderCard($context);
        }
        $output .= '</div>';

        if ($showMore) {
            $output .= '<p class="course-catalogue__more"><a href="'
              . $this->uri(
                  array('action' => 'catalogue'),
                  'context'
              ) . '">'
              . $this->escape($this->text(
                  'mod_context_viewmorecourses',
                  'View more courses'
              )) . '</a></p>';
        }

        return '<section class="course-catalogue">' . $output . '</section>'
          . $this->applicationNoticeScript();
    }

    /**
     * Bind the private-course application notice once per page.
     *
     * @return string Dependency-free event delegation script
     * @access private
     */
    private function applicationNoticeScript()
    {
        return '<script>(function(){'
          . 'if(window.chisimbaCourseApplicationNoticeBound){return;}'
          . 'window.chisimbaCourseApplicationNoticeBound=true;'
          . 'document.addEventListener("click",function(event){'
          . 'var button=event.target.closest("[data-course-application-notice]");'
          . 'if(!button){return;}'
          . 'window.alert(button.getAttribute("data-course-application-notice"));'
          . '});})();</script>';
    }

    /**
     * Render one course card.
     *
     * @param array $context Context record
     * @return string Semantic course card
     * @access private
     */
    private function renderCard(array $context)
    {
        $code = isset($context['contextcode'])
            ? (string) $context['contextcode']
            : '';
        $title = isset($context['title'])
            ? trim((string) $context['title'])
            : '';
        $access = isset($context['access_policy'])
            && trim((string) $context['access_policy']) !== ''
            ? strtolower((string) $context['access_policy'])
            : (isset($context['access'])
                ? ucfirst(strtolower((string) $context['access']))
                : 'Private');
        $image = $this->objContextImage->getContextImage($code);
        $summary = $this->summary(
            isset($context['about']) ? $context['about'] : ''
        );
        $lecturers = $this->lecturerNames($code);
        $action = $this->actionFor($context);
        if (isset($action['type']) && $action['type'] === 'notice') {
            $actionHtml = '<button type="button" class="course-card__action"'
              . ' data-course-application-notice="'
              . $this->escape($action['message']) . '">'
              . $this->escape($action['label']) . '</button>';
        } else {
            $actionHtml = '<a class="course-card__action" href="'
              . $action['url'] . '">'
              . $this->escape($action['label']) . '</a>';
        }

        $media = '<div class="course-card__placeholder" aria-hidden="true">'
          . '<span></span></div>';
        if ($image !== false && $image !== '') {
            $media = '<img class="course-card__image" src="'
              . $this->escape($image) . '" alt="" loading="lazy">';
        }

        $format = isset($context['delivery_format'])
            ? strtolower(trim((string) $context['delivery_format']))
            : '';
        $formatBadge = '';
        if ($format === 'microlearning') {
            $formatBadge = '<span class="course-card__badge">'
              . $this->escape($this->text(
                  'mod_context_formatmicrolearning',
                  'Microlearning'
              )) . '</span>';
        }

        $summaryHtml = $summary === '' ? ''
            : '<p class="course-card__summary">'
              . $this->escape($summary) . '</p>';
        $lecturerHtml = count($lecturers) === 0 ? ''
            : '<p class="course-card__lecturer"><span>'
              . $this->escape($this->text(
                  'mod_context_ledby',
                  'Led by'
              )) . '</span> '
              . $this->escape(implode(', ', $lecturers)) . '</p>';

        return '<article class="course-card" role="listitem">'
          . '<div class="course-card__media">' . $media
          . '<div class="course-card__badges"><span class="course-card__badge '
          . 'course-card__badge--access">' . $this->escape(
              $this->accessLabel($access)
          ) . '</span>' . $formatBadge . '</div></div>'
          . '<div class="course-card__body"><h3 class="course-card__title">'
          . $this->escape($title) . '</h3>' . $summaryHtml . $lecturerHtml
          . '<div class="course-card__footer">' . $actionHtml
          . '</div></div></article>';
    }

    /**
     * Determine the action presented for a course and the current visitor.
     *
     * @param array $context Context record
     * @return array Action label and URL
     * @access private
     */
    private function actionFor(array $context)
    {
        $code = (string) $context['contextcode'];
        $access = strtolower((string) $context['access']);
        $mappedPolicy = isset($context['access_policy'])
            ? strtolower(trim((string) $context['access_policy'])) : '';
        $isLoggedIn = $this->objUser->isLoggedIn();
        $isMember = $this->objUser->isAdmin()
            || in_array($code, $this->userContexts, true);

        if ($mappedPolicy !== '') {
            if ($mappedPolicy !== 'public' && !$isLoggedIn) {
                return array(
                    'label' => $this->text('mod_context_loginorjoinsite', 'Log in or join site'),
                    'url' => $this->uri(array('action' => 'showlogin'), 'security'),
                );
            }
            $allowed = $this->objUser->isAdmin();
            if (!$allowed) {
                try {
                    $decision = $this->getObject('accesspolicyservice', 'access-policy-service')->resolve(array(
                        'policy' => $mappedPolicy,
                        'resourceType' => 'course',
                        'resourceId' => $code,
                        'userId' => (string) $this->objUser->userId(),
                    ));
                    $allowed = !empty($decision['allowed']);
                } catch (Throwable $failure) {
                    $allowed = FALSE;
                }
            }
            if (!$allowed) {
                return array(
                    'type' => 'notice',
                    'label' => $this->text('mod_context_courseaccessrequired', 'Access required'),
                    'message' => $this->text('mod_context_courseaccessrequirement', 'This course requires the indicated membership or a specific course entitlement.'),
                );
            }
            return array(
                'label' => $this->text('mod_context_viewcourse', 'View course'),
                'url' => $this->uri(array('action' => 'joincontext', 'contextcode' => $code), 'context'),
            );
        }

        if ($access === 'private' && !$isMember) {
            return array(
                'type' => 'notice',
                'label' => $this->text(
                    'mod_context_applyforcourse',
                    'Apply for course'
                ),
                'message' => $this->text(
                    'mod_context_applicationscomingsoon',
                    'Online course applications will be available soon.'
                ),
            );
        }

        if ($access === 'open' && !$isLoggedIn) {
            return array(
                'label' => $this->text(
                    'mod_context_loginorjoinsite',
                    'Log in or join site'
                ),
                'url' => $this->uri(array('action' => 'showlogin'), 'security'),
            );
        }

        return array(
            'label' => $this->text(
                'mod_context_viewcourse',
                'View course'
            ),
            'url' => $this->uri(
                array('action' => 'joincontext', 'contextcode' => $code),
                'context'
            ),
        );
    }

    /**
     * Return the names of lecturers responsible for a course.
     *
     * Lecturer responsibility remains owned by context membership; catalogue
     * presentation does not duplicate names or user identifiers in context.
     *
     * @param string $contextCode Context code
     * @return array Lecturer display names
     * @access private
     */
    private function lecturerNames($contextCode)
    {
        $records = $this->objUserContext->getContextLecturers($contextCode);
        if (!is_array($records)) {
            return array();
        }

        $names = array();
        foreach ($records as $record) {
            $name = trim(
                (isset($record['firstname']) ? $record['firstname'] : '')
                . ' '
                . (isset($record['surname']) ? $record['surname'] : '')
            );
            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Return the translated label for an access category.
     *
     * @param string $access Normalised access category
     * @return string Translated access label
     * @access private
     */
    private function accessLabel($access)
    {
        $normalised = strtolower((string) $access);
        if ($normalised === 'public') {
            return $this->objLanguage->languageText('word_public', 'system');
        }
        if ($normalised === 'open') {
            return $this->objLanguage->languageText('word_open', 'system');
        }
        if ($normalised === 'free') { return 'Free'; }
        if ($normalised === 'tier_1') { return 'Tier 1'; }
        if ($normalised === 'tier_2') { return 'Tier 2'; }
        return $this->text('word_private', 'Private');
    }

    /**
     * Convert rich course description content to a compact card summary.
     *
     * @param string $value Course description
     * @return string Plain-text summary
     * @access private
     */
    private function summary($value)
    {
        $value = html_entity_decode(
            strip_tags((string) $value),
            ENT_QUOTES,
            'UTF-8'
        );
        $value = preg_replace('/\s+/u', ' ', trim($value));
        if ($value === null || $value === '') {
            return '';
        }
        if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > 180) {
            return rtrim(mb_substr($value, 0, 177, 'UTF-8')) . '...';
        }
        if (!function_exists('mb_strlen') && strlen($value) > 180) {
            return rtrim(substr($value, 0, 177)) . '...';
        }
        return $value;
    }

    /**
     * Retrieve a context language string with an installation-time fallback.
     *
     * @param string $key      Language key
     * @param string $fallback Fallback text
     * @return string Translated text
     * @access private
     */
    private function text($key, $fallback)
    {
        return $this->objLanguage->code2Txt(
            $key,
            'context',
            null,
            $fallback
        );
    }

    /**
     * Escape a value for HTML text or attribute output.
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     * @access private
     */
    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

?>
