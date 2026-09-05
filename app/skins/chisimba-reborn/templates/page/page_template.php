<?php
/*
 * chisimba-reborn canvas page template
 *
 * This is the page template in the chisimba-reborn skin
 * 
 * Notes: 
 *   1. There is no headerwrapper in this skin
 *
 */
// Add navigation back to top of page.
define("PAGETOP", '<span id="pagetop"></span>');
define("GOTOTOP", '<a href="#pagetop">Top</a>'); // @todo change this to an icon



// Get the four banner blocks
$objModuleCatalogue = $this->getObject('modules', 'modulecatalogue');
$isInstalled = $objModuleCatalogue->checkIfRegistered("bannerhelper");
$statusBadge = '';
$journeyBadges = '';
$bannerPillsDisabled = false;
try {
    $bannerPillsSetting = strtolower(trim((string) $this->getObject(
        'dbsysconfig', 'sysconfig'
    )->getValue('TOOLBAR_DISABLE_BANNER_PILLS', 'toolbar', 'FALSE')));
    $bannerPillsDisabled = in_array(
        $bannerPillsSetting,
        array('1', 'true', 'yes', 'on'),
        true
    );
} catch (Throwable $configurationFailure) {
    $bannerPillsDisabled = false;
}
if ($this->objUser->isLoggedIn() && !$bannerPillsDisabled) {
    try {
        $roleContext = $this->getObject(
            'toolbarsecuritycontext', 'toolbar'
        );
        $statusLabel = null;
        $statusHref = null;
        $isSiteAdministrator = $roleContext->isSiteAdministrator();
        $isAuthor = $roleContext->isLecturer();
        if ($isSiteAdministrator) {
            $statusLabel = 'Administrator';
        } elseif ($roleContext->isCurrentContextLecturer()
            || $roleContext->isLecturer()) {
            $statusLabel = ucfirst($this->objLanguage->code2Txt(
                'word_lecturer', 'system', null, '[-author-]'
            ));
        } elseif ($objModuleCatalogue->checkIfRegistered('membership-service')) {
            $heldTier = $this->getObject(
                'membershipservice', 'membership-service'
            )->effectiveTier($this->objUser->userId());
            $statusLabel = match ((string) $heldTier) {
                'tier_1' => 'Tier 1 member',
                'tier_2' => 'Tier 2 member',
                default => 'Free member',
            };
            if ($objModuleCatalogue->checkIfRegistered('payment-service')) {
                $statusHref = $this->uri(array(
                    'action' => 'catalogue', 'purpose' => 'membership'
                ), 'payment-service');
            }
        } elseif ($roleContext->hasStudentLearning()) {
            $statusLabel = 'Learner';
        }
        if ($statusLabel !== null) {
            $statusBadge = '<span class="chisimba-site-banner__status">'
                . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8')
                . '</span>';
        }
        if ($statusBadge !== '' && $statusHref !== null) {
            $statusBadge = '<a class="chisimba-site-banner__status-link" href="'
                . htmlspecialchars($statusHref, ENT_QUOTES, 'UTF-8') . '">'
                . $statusBadge . '</a>';
        }

        $icons = $this->getObject('iconservice', 'ui');
        $journeyLinks = array();
        $requestedModule = (string) $this->getParam('module', '');
        $requestedAction = (string) $this->getParam('action', '');
        $isJourneyCurrent = static function ($module, $action = '') use (
            $requestedModule,
            $requestedAction
        ) {
            return $requestedModule === $module
                && $requestedAction === $action;
        };
        $context = $this->getObject('dbcontext', 'context');
        if ($context->isInContext()) {
            $contextCode = $context->getContextCode();
            $contextDetails = $context->getContextDetails($contextCode);
            $contextTitle = trim((string) ($contextDetails['title'] ?? ''));
            if ($contextTitle !== '') {
                $currentLabel = ucfirst($this->objLanguage->code2Txt(
                    'mod_toolbar_currentcontext',
                    'toolbar',
                    null,
                    'Current [-context-]'
                ));
                $journeyLinks[] = array(
                    'class' => 'current',
                    'icon' => 'book-open',
                    'label' => $contextTitle,
                    'title' => $currentLabel . ': ' . $contextTitle,
                    'url' => $this->uri(array('action' => 'home'), 'context'),
                    'isCurrent' => $isJourneyCurrent('context', 'home'),
                );
            }
        }
        if ($isSiteAdministrator) {
            if ($objModuleCatalogue->checkIfRegistered('myadmin')) {
                $journeyLinks[] = array(
                    'class' => 'administration',
                    'icon' => 'activity',
                    'label' => $this->objLanguage->languageText(
                        'mod_toolbar_myadministration',
                        'toolbar',
                        'My Administration'
                    ),
                    'title' => '',
                    'url' => $this->uri(null, 'myadmin'),
                    'isCurrent' => $isJourneyCurrent('myadmin'),
                );
            }
            $journeyLinks[] = array(
                'class' => 'site-administration',
                'icon' => 'settings',
                'label' => $this->objLanguage->languageText(
                    'mod_toolbar_siteadministration',
                    'toolbar',
                    'Site Administration'
                ),
                'title' => '',
                'url' => $this->uri(null, 'toolbar'),
                'isCurrent' => $isJourneyCurrent('toolbar'),
            );
        } else {
            if ($isAuthor
                && $objModuleCatalogue->checkIfRegistered('myteaching')) {
                $journeyLinks[] = array(
                    'class' => 'teaching',
                    'icon' => 'layout-dashboard',
                    'label' => $this->objLanguage->languageText(
                        'mod_toolbar_allteachingcontexts',
                        'toolbar',
                        'My Teaching'
                    ),
                    'title' => '',
                    'url' => $this->uri(null, 'myteaching'),
                    'isCurrent' => $isJourneyCurrent('myteaching'),
                );
            }
            if ($roleContext->hasStudentLearning()
                && $objModuleCatalogue->checkIfRegistered('mylearning')) {
                $journeyLinks[] = array(
                    'class' => 'learning',
                    'icon' => 'graduation-cap',
                    'label' => $this->objLanguage->languageText(
                        'mod_toolbar_alllearningcontexts',
                        'toolbar',
                        'My Learning'
                    ),
                    'title' => '',
                    'url' => $this->uri(null, 'mylearning'),
                    'isCurrent' => $isJourneyCurrent('mylearning'),
                );
            }
            if ($isAuthor
                && $objModuleCatalogue->checkIfRegistered('contextadmin')) {
                $journeyLinks[] = array(
                    'class' => 'create',
                    'icon' => 'plus',
                    'label' => ucfirst($this->objLanguage->code2Txt(
                        'mod_toolbar_createcourse',
                        'toolbar',
                        null,
                        'Create [-context-]'
                    )),
                    'title' => '',
                    'url' => $this->uri(array('action' => 'add'), 'contextadmin'),
                    'isCurrent' => $isJourneyCurrent('contextadmin', 'add'),
                );
            }
        }
        foreach ($journeyLinks as $journeyLink) {
            $title = $journeyLink['title'] === ''
                ? ''
                : ' title="' . htmlspecialchars(
                    $journeyLink['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) . '"';
            $tag = $journeyLink['isCurrent'] ? 'span' : 'a';
            $currentClass = $journeyLink['isCurrent']
                ? ' chisimba-site-banner__journey--active'
                : '';
            $journeyBadges .= '<' . $tag
                . ' class="chisimba-site-banner__journey '
                . 'chisimba-site-banner__journey--'
                . htmlspecialchars($journeyLink['class'], ENT_QUOTES, 'UTF-8')
                . $currentClass . '"'
                . ($journeyLink['isCurrent']
                    ? ' aria-current="page"'
                    : ' href="' . htmlspecialchars(
                        $journeyLink['url'], ENT_QUOTES, 'UTF-8'
                    ) . '"')
                . $title . '>'
                . $icons->render($journeyLink['icon'], array('decorative' => true))
                . '<span>'
                . htmlspecialchars($journeyLink['label'], ENT_QUOTES, 'UTF-8')
                . '</span></' . $tag . '>';
        }
    } catch (Throwable $membershipFailure) {
        $statusBadge = '';
        $journeyBadges = '';
    }
}
if ($isInstalled) {
    $objBl = $this->getObject('fsbannerhelper', 'bannerhelper');
    $banner0 = $objBl->readContents("banner0");
    $banner1 = $objBl->readContents("banner1");
    $banner2 = $objBl->readContents("banner2");
    $banner3 = $objBl->readContents("banner3");
    $plMenu = $objBl->readContents("plmenu");
} else {
    $banner0 = NULL;
    $banner1 = NULL;
    $banner2 = NULL;
    $banner3 = NULL;
    $plMenu = NULL;
}

// Initialise the variable holding preferred canvas
$prefCanvas=FALSE;

// Initialise the layout settings
$setCanvas = FALSE;

// Define the name of this skin.
$skinName = "chisimba-reborn";

// Define the valid canvases for this skin as an array.
$validCanvases = array_map('basename', glob('skins/' . $skinName . '/canvases/*', GLOB_ONLYDIR));

// Settings needed so that canvas-aware code can function.
$this->setSession('skinName', $skinName);
$_SESSION['skinName'] = $skinName;
$_SESSION['isCanvas'] = TRUE;
$_SESSION['sourceSkin'] = $skinName;
$_SESSION['layout'] = '_DEFAULT';

// Instantiate the canvas object.
$objCanvas = $this->getObject('canvaschooser', 'canvas');

// Set the skin base for the default.
$skinBase='skins/' . $skinName . '/canvases/';
if (isset ($canvas)) {
    $_SESSION['canvasType'] = 'programmatic';
    $_SESSION['canvas'] = $canvas;
    $canvas = $skinBase . $canvas;
} elseif ($prefCanvas) {
    $canvas = $skinBase . $prefCanvas;
} else {
    // Get what canvas we should be showing
    $canvas = $objCanvas->getCanvas($validCanvases, $skinBase);
}

// Check if there is a settings file and load it. Use the canonical skin path
// rather than the requested skin object's path so compatibility entry points
// can delegate here without retaining their own canvas implementation.
if (!isset($pageSuppressSkin)) {
    $canvasName = $objCanvas->getCanvasName($canvas);
    $settingsFile = $objConfig->getSiteRootPath()
        . 'skins/' . $skinName . '/canvases/' . $canvasName . '/settings.php';
    if(file_exists($settingsFile)) {
        require_once $settingsFile;
    }
}

// Get Header that goes into every skin.
$siteRootPath = $objConfig->getsiteRootPath();
require($siteRootPath . 'skins/_common/templates/skinpageheader3-0.php');


// Set up the open graph stuff
if (!isset($og_title)) {
    $og_title = $pageTitle;
}
if (!isset($og_image)) {
    $og_image = 'skins/' . $skinName . '/default.png';
}
if (!isset($og_content)) {
    $og_content = 'Chisimba is a PHP framework for building web applications 
        and applications that need a web API. It implements a 
        model-view-controller (MVC) design pattern, implemented 
        on a modular architecture. There is a core framework, 
        and numerous modules that implement functionality ranging 
        from blogs through CMS to a eLearning system. The interface 
        design is flexible and implemented via canvases (skins, or 
        themes). There is an online package management system, and 
        developers can build modules rapidly by generating a working 
        module from which to code.';
} else {
    $og_content = strip_tags($og_content);
}

// Branding assets belong to the selected canvas. Keep the skin favicon as a
// compatibility fallback for canvases that have not supplied their own yet.
$skinFavicon = 'skins/' . $skinName . '/favicon.png';
$favicon = $skinFavicon;
if (!isset($pageSuppressSkin)) {
    $canvasFavicon = 'skins/' . $skinName . '/canvases/'
        . $canvasName . '/favicon.png';
    if (is_file($siteRootPath . $canvasFavicon)) {
        $favicon = $canvasFavicon;
    }
}
$faviconVersion = is_file($siteRootPath . $favicon)
    ? '?v=' . filemtime($siteRootPath . $favicon)
    : '';



// Render the head section of the page. Note that there can be no space or
// blank lines between the PHP closing tag and the HTML head tag. It must be
// exactly as below.
?><head>
    <meta property="og:title" content="<?php echo htmlspecialchars($og_title, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($og_content, ENT_QUOTES, 'UTF-8'); ?>" />
    <title>
        <?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>
    </title>
    <?php
    // Get the skin version 2 base CSS for all skins.
    if (!isset($pageSuppressSkin)) {
        echo '

        <link rel="stylesheet" type="text/css" href="skins/_common2/css/basecss.php">
        <link rel="icon" type="image/png" href="'
            . htmlspecialchars(
                $favicon . $faviconVersion,
                ENT_QUOTES,
                'UTF-8'
            ) . '" />
            
        ';
     }


    // Render the javascript unless it is suppressed.
    if (!isset($pageSuppressJavascript)) {
        echo $objSkin->putJavaScript($mime, $headerParams);
        // Load the helper JS from the current skin
        $helperJs = 'skins/' . $skinName . '/javascript/skinhelper.js';
        echo "\n<script type='text/javascript' src='" . $helperJs . "'></script>\n\n";
    }

    // Render the CSS for the current skin unless it is suppressed.
    if (!isset($pageSuppressSkin)) {
       $skinCss = 'skins/' . $skinName . '/stylesheet.css';
       $baseCanvasCss = 'skins/' . $skinName
           . '/canvases/_default/stylesheet.css';
       $canvasCss = $canvas . '/stylesheet.css';
       $skinCssVersion = is_file($skinCss) ? '?v=' . filemtime($skinCss) : '';
       $baseCanvasCssVersion = is_file($baseCanvasCss)
           ? '?v=' . filemtime($baseCanvasCss) : '';
       $canvasCssVersion = is_file($canvasCss) ? '?v=' . filemtime($canvasCss) : '';
       echo '

       <link rel="stylesheet" type="text/css" href="' . $skinCss . $skinCssVersion . '">
       <link rel="stylesheet" type="text/css" href="' . $baseCanvasCss . $baseCanvasCssVersion . '">
       <link rel="stylesheet" type="text/css" href="' . $canvasCss . $canvasCssVersion . '">

        ';
    }
    ?>
</head>

<?php
// Render body parameters if they are set, otherwise render a plain body tag
if (isset($bodyParams)) {
    echo '<body '.$bodyParams.'>';
} else {
    echo '<body>';
}

// Render the container & canvas elements unless it is suppressed.
if (!isset($pageSuppressContainer)) { ?>
    <div id='OutermostWrapper'>
        <div class='ChisimbaCanvas' id='<?php echo htmlspecialchars($canvasName ?? '_default', ENT_QUOTES, 'UTF-8'); ?>'>
            <div id='Canvas_Content'>
                <div id='Canvas_BeforeContainer'></div>
                <div id='container'>
<?php
}

// Render the banner area unless it is suppressed
if (!isset($pageSuppressBanner)) {
    // Because the link to page top is in the footer, put the top here
    // only if the footer is not suppressed.
    if (!isset($suppressFooter)) {
        echo PAGETOP;
    }
    echo "\n\n<div class='Canvas_Content_Head_Before'>";
    if (!isset($pageSuppressSearch)) {
        //echo $objSkin->siteSearchBox();
    }
    echo "</div>\n\n"
    ?>
    <header class="Canvas_Content_Head chisimba-site-banner"
        aria-label="Site banner">
        <div class="Canvas_Content_Head_Header chisimba-site-banner__inner"
            id="header">
            <?php
            echo '<a class="sitename_link chisimba-site-banner__brand" '
                . 'href="' . htmlspecialchars($objConfig->getSiteRoot(), ENT_QUOTES, 'UTF-8') . '">';
            ?>
            <span class="chisimba-site-banner__identity" aria-hidden="true"></span>
            <span class="chisimba-site-banner__name" id="sitename">
                <?php echo htmlspecialchars($objConfig->getsiteName(), ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php echo '</a>'; ?>
            <?php if ($journeyBadges !== '' || $statusBadge !== ''): ?>
                <div class="chisimba-site-banner__utilities">
                    <?php echo $journeyBadges . $statusBadge; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class='floathead' id='floathead_content3'><?php echo $banner3; ?></div>
        <div class='floathead' id='floathead_content2'><?php echo $banner2; ?></div>
        <div class='floathead' id='floathead_content1'><?php echo $banner1; ?></div>
        <div class='floathead' id='floathead_content0'><?php echo $banner0; ?></div>
        <?php
}

if (!isset($pageSuppressBanner)) {
    echo "</header>";
    if (!isset($pageSuppressToolbar)) {
        $simulate = $this->getParam('simulate', NULL);
        if (!$this->objUser->isLoggedIn() || ($simulate == 'prelogintoolbar')) {
            if ($isInstalled) {
                echo "\n\n<div id='prelogin_nav'>$plMenu</div>\n\n";
            }
        } else {
            echo "\n\n<div id='navigation'>\n\n" . $toolbar . "\n</div>\n\n";
        }
        
    }
    echo '<div class="Canvas_Content_Head_After"></div>';
}


// Render the layout content as supplied from the layout template
echo "<div class='Canvas_Content_Body_Before'></div>\n"
   . "<div id='Canvas_Content_Body'>\n"
   . $this->getLayoutContent()
   . "</div>\n<div class='Canvas_Content_Body_After'></div>\n"
   .'<br id="footerbr" />';


// If the footer is not suppressed, render it out.
if (!isset($suppressFooter)) {
    // Add the footer string if it is set
    if ($objUser->isLoggedIn()) {
        // The authenticated footer is status only. Logout belongs solely to
        // the toolbar and must not be inherited from a legacy footer string.
        $str = $objLanguage->languageText(
            "mod_context_loggedinas",
            'context'
        ) . ' <strong>' . $objUser->fullname() . '</strong>';
        $footerStr = $str;

        // Keep the active working scope visible throughout the application.
        // A module may still show the scope of an individual resource where
        // that differs from the page's current course/root/personal scope.
        $objFooterContext = $this->getObject('dbcontext', 'context');
        if ($objFooterContext->isInContext()) {
            $footerScopeType = ucfirst($objLanguage->code2Txt(
                'mod_toolbar_scope_course',
                'toolbar'
            ));
            $footerContextCode = $objFooterContext->getContextCode();
            $footerContext = $objFooterContext->getContextDetails($footerContextCode);
            $footerScopeValue = $footerScopeType . ' — ' . $footerContext['title'];
        } elseif ($this->getParam('module', '') === 'personalspace') {
            $footerScopeValue = $objLanguage->languageText(
                'mod_toolbar_scope_personal',
                'toolbar'
            );
        } else {
            $footerScopeValue = $objLanguage->languageText(
                'mod_toolbar_scope_root',
                'toolbar'
            );
        }
        $footerScope = '<span class="chisimba-site-footer__scope"><strong>'
            . htmlspecialchars($objLanguage->languageText('mod_toolbar_scope', 'toolbar'), ENT_QUOTES, 'UTF-8')
            . ':</strong> '
            . htmlspecialchars($footerScopeValue, ENT_QUOTES, 'UTF-8')
            . '</span>';
    } elseif (!isset($footerStr)) {
        $footerStr = $objLanguage->languageText("mod_security_poweredby", 'security', 'Powered by ') . ' Chisimba';
    }
    /*
     * Render the semantic application footer.
     *
     * The session/account string remains framework supplied. The skin adds
     * stable structure and a separately styled return-to-top action without
     * changing authentication or language behaviour.
     */
    echo "<div class='Canvas_Content_Footer_Before'></div>"
      . "<footer class='Canvas_Content_Footer chisimba-site-footer'>"
      . "<div id='footer' class='chisimba-site-footer__inner'>"
      . "<div class='chisimba-site-footer__status'>"
      . $footerStr
      . "</div>";

    if (isset($footerScope)) {
        echo $footerScope;
    }

    // Put in the link to the top of the page.
    if (!isset($pageSuppressBanner)) {
        echo "<div class='chisimba-site-footer__actions'>"
          . GOTOTOP
          . "</div>";
    }

    echo "</div>\n</footer>\n"
      . "<div class='Canvas_Content_Footer_After'></div>";
}
// Render the container's closing div if the container is not suppressed
if (!isset($pageSuppressContainer)) {
    echo "</div><div class='Canvas_AfterContainer'></div>\n</div>\n</div></div>";
}



// Render any messages available.
$this->putMessages();

// Close the body and HTML.
?>

</body>
</html>
