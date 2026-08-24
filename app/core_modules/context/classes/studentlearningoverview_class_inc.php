<?php
/** Cross-course learner overview used by the canonical post-login home. */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class studentlearningoverview extends ChisimbaObject
{
    private $language;
    private $user;
    private $userContext;
    private $context;
    private $modules;

    public function init()
    {
        $this->language = $this->getObject('language', 'language');
        $this->user = $this->getObject('user', 'security');
        $this->userContext = $this->getObject('usercontext', 'context');
        $this->context = $this->getObject('dbcontext', 'context');
        $this->modules = $this->getObject('modules', 'modulecatalogue');
    }

    public function show()
    {
        if (!$this->user->isLoggedIn()
            || !$this->modules->checkIfRegistered('contextcontent')) {
            return '';
        }

        $userId = $this->user->userId();
        $journey = $this->getObject('learningjourney', 'contextcontent');
        $courses = array();
        foreach ((array) $this->userContext->getUserContext($userId) as $code) {
            $details = $this->context->getContextDetails($code);
            if (!is_array($details) || empty($details['title'])) {
                continue;
            }
            if (($details['status'] ?? '') === 'Unpublished'
                && !$this->user->isContextLecturer($userId, $code)
                && !$this->user->isAdmin()) {
                continue;
            }
            $state = $journey->getState($code, $userId);
            if (!is_array($state) || empty($state['available'])) {
                continue;
            }
            $courses[] = array(
                'code' => (string) $code,
                'title' => (string) $details['title'],
                'state' => $state,
            );
        }

        if ($courses === array()) {
            return '';
        }

        usort($courses, static function ($left, $right) {
            $leftDate = (string) ($left['state']['lastactivity'] ?? '');
            $rightDate = (string) ($right['state']['lastactivity'] ?? '');
            if ($leftDate !== $rightDate) {
                return strcmp($rightDate, $leftDate);
            }
            $leftStarted = !empty($left['state']['started']);
            $rightStarted = !empty($right['state']['started']);
            if ($leftStarted !== $rightStarted) {
                return $rightStarted <=> $leftStarted;
            }
            return strcasecmp($left['title'], $right['title']);
        });

        return $this->render($courses);
    }

    private function render(array $courses)
    {
        $e = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };
        $text = function ($key, $fallback) {
            return $this->language->languageText(
                'mod_context_' . $key,
                'context',
                $fallback
            );
        };

        $html = '<section class="student-learning-overview" aria-labelledby="my-learning-title">';
        $html .= '<header class="student-learning-overview__header">';
        $html .= '<div><p class="student-learning-overview__eyebrow">'
            . $e($text('mylearningeyebrow', 'Your learning')) . '</p>';
        $html .= '<h1 id="my-learning-title">'
            . $e($text('mylearningtitle', 'My Learning')) . '</h1>';
        $html .= '<p>' . $e($text(
            'mylearningintro',
            'Continue where you left off or choose another course.'
        )) . '</p></div>';
        $html .= '<span class="student-learning-overview__count">'
            . count($courses) . ' ' . $e(count($courses) === 1
                ? $text('mylearningcourse', 'course')
                : $text('mylearningcourses', 'courses')) . '</span>';
        $html .= '</header><div class="student-learning-overview__grid">';

        foreach ($courses as $index => $course) {
            $state = $course['state'];
            $started = !empty($state['started']);
            $total = max(0, (int) ($state['total'] ?? 0));
            $visited = min($total, max(0, (int) ($state['visited'] ?? 0)));
            $percent = $total > 0 ? (int) round(($visited / $total) * 100) : 0;
            $nextTitle = trim((string) ($state['pagetitle'] ?? ''));
            $actionUrl = $this->uri(array(
                'action' => 'joincontext',
                'contextcode' => $course['code'],
                'contextmodule' => 'contextcontent',
                'contextaction' => 'viewpage',
                'contextdata' => (string) ($state['pageid'] ?? ''),
            ), 'context');

            $html .= '<article class="student-learning-course'
                . ($index === 0 ? ' student-learning-course--next' : '') . '">';
            $html .= '<div class="student-learning-course__topline"><span>'
                . $e($started
                    ? $text('mylearninginprogress', 'In progress')
                    : $text('mylearningnew', 'Not started')) . '</span>';
            if ($index === 0) {
                $html .= '<strong>'
                    . $e($text('mylearningnext', 'Continue next')) . '</strong>';
            }
            $html .= '</div><h2>' . $e($course['title']) . '</h2>';

            if ($total > 0) {
                $html .= '<div class="student-learning-course__progress-head"><span>'
                    . $e($text('mylearningprogress', 'Progress')) . '</span><span>'
                    . $visited . ' / ' . $total . '</span></div>';
                $html .= '<div class="student-learning-course__track" role="progressbar"'
                    . ' aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percent . '">'
                    . '<span style="width:' . $percent . '%"></span></div>';
            }
            if ($nextTitle !== '') {
                $html .= '<p class="student-learning-course__next"><span>'
                    . $e($started
                        ? $text('mylearningcontinuewith', 'Continue with')
                        : $text('mylearningstartwith', 'Start with'))
                    . '</span><strong>' . $e($nextTitle) . '</strong></p>';
            }
            $html .= '<a class="button student-learning-course__action" href="'
                . $e($actionUrl) . '">' . $e($started
                    ? $text('mylearningcontinue', 'Continue learning')
                    : $text('mylearningstart', 'Start course')) . '</a>';
            $html .= '</article>';
        }

        return $html . '</div></section>';
    }
}
?>
