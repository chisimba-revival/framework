<?php
/**
 * Personal learning-journey block for a [-context-] home.
 *
 * Context owns block placement/presentation. ContextContent owns learning
 * position and visit interpretation through its learningjourney service.
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

class block_learningjourney extends ChisimbaObject
{
    public $title;
    public $showTitle = FALSE;
    public $cssClass = 'chisimba-learning-journey-block-shell';

    /** Render as a complete primary content surface. */
    public $presentationMode = 'content';

    private $objLanguage;
    private $objUser;
    private $objContext;
    private $objModules;

    public function init()
    {
        $this->objLanguage = $this->getObject('language', 'language');
        $this->objUser = $this->getObject('user', 'security');
        $this->objContext = $this->getObject('dbcontext', 'context');
        $this->objModules = $this->getObject('modules', 'modulecatalogue');

        $this->title = $this->objLanguage->languageText(
            'mod_context_learningjourney',
            'context',
            'Your learning journey'
        );
    }

    public function show()
    {
        $contextCode = $this->objContext->getContextCode();
        if (empty($contextCode)) {
            return '';
        }

        // ContextContent may hold valid course content even when the legacy
        // per-context plugin table has no row. The journey service itself is
        // the authoritative availability check for this block.
        if (!method_exists($this->objModules, 'checkIfRegistered')
            || !$this->objModules->checkIfRegistered('contextcontent')) {
            return '';
        }

        $objJourney = $this->getObject('learningjourney', 'contextcontent');
        $userId = $this->objUser->isLoggedIn() ? $this->objUser->userId() : '';
        $state = $objJourney->getState($contextCode, $userId);

        if (!is_array($state) || empty($state['available'])) {
            return '';
        }

        return $this->renderJourney($state);
    }

    private function renderJourney(array $state)
    {
        $contextCode = (string) $this->objContext->getContextCode();
        $isManager = $this->objUser->isLoggedIn()
            && $this->objUser->isCourseAdmin($contextCode);
        $started = !empty($state['started']);
        $pageId = isset($state['pageid']) ? $state['pageid'] : '';
        $pageTitle = isset($state['pagetitle']) ? $state['pagetitle'] : '';
        $total = isset($state['total']) ? (int) $state['total'] : 0;
        $visited = isset($state['visited']) ? (int) $state['visited'] : 0;
        $bookmarks = isset($state['bookmarks']) && is_array($state['bookmarks'])
            ? $state['bookmarks']
            : array();

        $eyebrow = $this->objLanguage->languageText(
            $isManager ? 'mod_context_coursemanagement' : 'mod_context_learningjourney',
            'context',
            $isManager ? 'Course management' : 'Your learning journey'
        );

        $fullName = trim((string) $this->objUser->fullname());
        $firstName = '';
        if ($fullName !== '') {
            $parts = preg_split('/\s+/', $fullName);
            if (is_array($parts) && count($parts) > 0) {
                $firstName = trim($parts[0]);
            }
        }

        $heading = $started
            ? $this->objLanguage->languageText(
                'mod_context_journeywelcomeback',
                'context',
                'Welcome back'
            )
            : $this->objLanguage->languageText(
                'mod_context_journeywelcome',
                'context',
                'Welcome'
            );

        if ($firstName !== '') {
            $heading .= ', ' . $firstName;
        }

        $lead = $isManager
            ? $this->objLanguage->languageText(
                'mod_context_managerjourneylead',
                'context',
                'Manage learning, people and course settings'
            )
            : ($started
            ? $this->objLanguage->languageText(
                'mod_context_journeypickup',
                'context',
                'Continue where you left off'
            )
            : $this->objLanguage->languageText(
                'mod_context_journeyready',
                'context',
                'Ready to begin?'
            ));

        $action = $isManager
            ? $this->objLanguage->code2Txt(
                'mod_context_managerjourneyaction',
                'context',
                NULL,
                'Manage this course'
            )
            : ($started
            ? $this->objLanguage->languageText(
                'mod_context_journeycontinue',
                'context',
                'Continue your learning journey'
            )
            : $this->objLanguage->languageText(
                'mod_context_journeystart',
                'context',
                'Start your learning journey'
            ));

        $status = $isManager
            ? ($this->objUser->isAdmin()
                ? $this->objLanguage->languageText(
                    'mod_context_administratorview',
                    'context',
                    'Administrator view'
                )
                : $this->objLanguage->code2Txt(
                    'mod_context_lecturerview',
                    'context',
                    null,
                    '[-author-] view'
                ))
            : ($started
            ? $this->objLanguage->languageText(
                'mod_context_journeystatusprogress',
                'context',
                'In progress'
            )
            : $this->objLanguage->languageText(
                'mod_context_journeystatusnew',
                'context',
                'New'
            ));

        $destination = $started
            ? $this->objLanguage->languageText(
                'mod_context_journeycontinuelabel',
                'context',
                'Continue with'
            )
            : $this->objLanguage->languageText(
                'mod_context_journeystartlabel',
                'context',
                'Start with'
            );

        $progressHeading = $this->objLanguage->languageText(
            'mod_context_journeyprogressheading',
            'context',
            'Your progress'
        );

        $visitedLabel = $this->objLanguage->languageText(
            'mod_context_journeyvisited',
            'context',
            'learning pages visited'
        );

        $bookmarksHeading = $this->objLanguage->languageText(
            'mod_contextcontent_viewbookmarkedpages',
            'contextcontent',
            'View Bookmarked Pages'
        );
        $viewAllBookmarks = $this->objLanguage->languageText(
            'mod_contextcontent_view_all_bookmarks',
            'contextcontent'
        );
        $clearBookmarks = $this->objLanguage->languageText(
            'mod_contextcontent_clear_bookmarks',
            'contextcontent'
        );
        $clearBookmarksConfirm = $this->objLanguage->languageText(
            'mod_contextcontent_clear_bookmarks_confirm',
            'contextcontent'
        );
        $clearBookmarksError = $this->objLanguage->languageText(
            'mod_contextcontent_bookmarks_clear_error',
            'contextcontent'
        );

        $url = $isManager
            ? $this->uri(array('action' => 'controlpanel'), 'context')
            : $this->uri(
                array('action' => 'viewpage', 'id' => $pageId),
                'contextcontent'
            );

        $e = function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $html = '<section class="chisimba-learning-journey chisimba-learning-journey--polished"'
            . ' aria-labelledby="chisimba-learning-journey-title">';
        $html .= '<div class="chisimba-learning-journey__inner">';

        $html .= '<div class="chisimba-learning-journey__main">';
        $html .= '<div class="chisimba-learning-journey__topline">';
        $html .= '<p class="chisimba-learning-journey__eyebrow">' . $e($eyebrow) . '</p>';
        $html .= '<span class="chisimba-learning-journey__status">' . $e($status) . '</span>';
        $html .= '</div>';

        $html .= '<h1 id="chisimba-learning-journey-title"'
            . ' class="chisimba-learning-journey__title">' . $e($heading) . '</h1>';
        $html .= '<p class="chisimba-learning-journey__lead">' . $e($lead) . '</p>';

        $html .= '<div class="chisimba-learning-journey__actions">';
        $html .= '<a class="chisimba-learning-journey__action" href="' . $url . '">'
            . $e($action) . '<span aria-hidden="true"> →</span></a>';
        $html .= '</div>';

        if ($started && $total > 0) {
            $visited = min($visited, $total);
            $percent = (int) round(($visited / $total) * 100);

            $html .= '<div class="chisimba-learning-journey__progress">';
            $html .= '<div class="chisimba-learning-journey__progress-head">';
            $html .= '<span class="chisimba-learning-journey__progress-label">'
                . $e($progressHeading) . '</span>';
            $html .= '<span class="chisimba-learning-journey__progress-value">'
                . $visited . ' / ' . $total . '</span>';
            $html .= '</div>';
            $html .= '<div class="chisimba-learning-journey__track" aria-hidden="true">'
                . '<span style="width:' . $percent . '%"></span></div>';
            $html .= '<p class="chisimba-learning-journey__meta">'
                . $visited . ' / ' . $total . ' ' . $e($visitedLabel) . '</p>';
            $html .= '</div>';
        }

        $html .= '</div>';

        $html .= '<aside class="chisimba-learning-journey__aside" aria-label="'
            . $e($destination) . '">';
        $html .= '<div class="chisimba-learning-journey__card">';
        $html .= '<p class="chisimba-learning-journey__card-label">'
            . $e($destination) . '</p>';

        if ($pageTitle !== '') {
            $html .= '<p class="chisimba-learning-journey__card-title">'
                . $e($pageTitle) . '</p>';
        }

        if ($started && $total > 0) {
            $html .= '<p class="chisimba-learning-journey__card-meta">'
                . min($visited, $total) . ' / ' . $total . ' '
                . $e($visitedLabel) . '</p>';
        }

        if ($bookmarks !== array()) {
            $html .= '<div class="chisimba-learning-journey__bookmarks">';
            $html .= '<p class="chisimba-learning-journey__card-label">'
                . $e($bookmarksHeading) . '</p>';
            $html .= '<ul class="chisimba-learning-journey__bookmark-list">';
            foreach (array_slice($bookmarks, 0, 3) as $bookmark) {
                $bookmarkId = isset($bookmark['pageid']) ? (string) $bookmark['pageid'] : '';
                $bookmarkTitle = isset($bookmark['pagetitle']) ? (string) $bookmark['pagetitle'] : '';
                if ($bookmarkId === '' || $bookmarkTitle === '') {
                    continue;
                }
                $bookmarkUrl = $this->uri(
                    array('action' => 'viewpage', 'id' => $bookmarkId),
                    'contextcontent'
                );
                $html .= '<li><a href="' . $bookmarkUrl . '">'
                    . $e($bookmarkTitle) . '</a></li>';
            }
            $html .= '</ul>';

            if (count($bookmarks) > 3) {
                $html .= '<details class="chisimba-learning-journey__bookmark-all">';
                $html .= '<summary>' . $e($viewAllBookmarks) . '</summary>';
                $html .= '<ul class="chisimba-learning-journey__bookmark-list">';
                foreach ($bookmarks as $bookmark) {
                    $bookmarkId = isset($bookmark['pageid']) ? (string) $bookmark['pageid'] : '';
                    $bookmarkTitle = isset($bookmark['pagetitle']) ? (string) $bookmark['pagetitle'] : '';
                    if ($bookmarkId === '' || $bookmarkTitle === '') {
                        continue;
                    }
                    $bookmarkUrl = $this->uri(
                        array('action' => 'viewpage', 'id' => $bookmarkId),
                        'contextcontent'
                    );
                    $html .= '<li><a href="' . $bookmarkUrl . '">'
                        . $e($bookmarkTitle) . '</a></li>';
                }
                $html .= '</ul></details>';
            }

            $clearItems = array();
            $clearBatch = array_slice($bookmarks, 0, 12);
            $stack = $this->getObject('nativeauthwebcomposition', 'security')->build();
            $csrf = isset($stack['csrf']) ? $stack['csrf'] : null;
            if (is_object($csrf) && method_exists($csrf, 'issue')) {
                foreach ($clearBatch as $bookmark) {
                    $bookmarkId = isset($bookmark['pageid']) ? (string) $bookmark['pageid'] : '';
                    if ($bookmarkId === '') {
                        continue;
                    }
                    $clearItems[] = array(
                        'id' => $bookmarkId,
                        'csrf' => $csrf->issue('contextcontent_authoring')
                    );
                }
            }

            if ($clearItems !== array()) {
                $clearUrl = $this->uri(array('action' => 'changebookmark'), 'contextcontent');
                $html .= '<div class="chisimba-learning-journey__bookmark-actions">';
                $html .= '<button type="button" class="chisimba-learning-journey__clear-bookmarks"'
                    . ' data-url="' . $clearUrl . '"'
                    . ' data-items="' . $e(json_encode($clearItems)) . '"'
                    . ' data-more="' . (count($bookmarks) > count($clearItems) ? '1' : '0') . '"'
                    . ' data-confirm="' . $e($clearBookmarksConfirm) . '"'
                    . ' data-error="' . $e($clearBookmarksError) . '">'
                    . $e($clearBookmarks) . '</button>';
                $html .= '<span class="chisimba-learning-journey__bookmark-feedback" role="status" aria-live="polite"></span>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div></aside>';
        $html .= '</div></section>';

        $html .= '<script type="text/javascript">(function(){'
            . 'var storageKey="chisimba.clearContextBookmarks";'
            . 'var button=document.querySelector(".chisimba-learning-journey__clear-bookmarks");'
            . 'if(!button){try{window.sessionStorage.removeItem(storageKey);}catch(error){}return;}'
            . 'var clearBatch=function(skipConfirm){'
            . 'if(button.disabled){return;}'
            . 'if(!skipConfirm&&!window.confirm(button.dataset.confirm)){return;}'
            . 'var items=[];try{items=JSON.parse(button.dataset.items||"[]");}catch(error){items=[];}'
            . 'if(!items.length){return;}button.disabled=true;'
            . 'var feedback=button.parentNode.querySelector(".chisimba-learning-journey__bookmark-feedback");'
            . 'var index=0;var removeNext=function(){'
            . 'if(index>=items.length){'
            . 'try{if(button.dataset.more==="1"){window.sessionStorage.setItem(storageKey,"1");}else{window.sessionStorage.removeItem(storageKey);}}catch(error){}'
            . 'window.location.reload();return;}'
            . 'var item=items[index++];var body=new URLSearchParams();'
            . 'body.set("csrf_token",item.csrf);body.set("id",item.id);body.set("type","off");body.set("ajax","1");'
            . 'fetch(button.dataset.url,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8","X-Requested-With":"XMLHttpRequest","Accept":"application/json"},body:body.toString(),credentials:"same-origin"})'
            . '.then(function(response){if(!response.ok){throw new Error("request");}return response.json();})'
            . '.then(function(data){if(!data||data.ok!==true||data.bookmarked!==false){throw new Error("state");}removeNext();})'
            . '.catch(function(){button.disabled=false;try{window.sessionStorage.removeItem(storageKey);}catch(error){}if(feedback){feedback.textContent=button.dataset.error;}});'
            . '};removeNext();};'
            . 'button.addEventListener("click",function(event){event.preventDefault();clearBatch(false);});'
            . 'try{if(window.sessionStorage.getItem(storageKey)==="1"){clearBatch(true);}}catch(error){}'
            . '})();</script>';

        return $html;
    }
}
?>
