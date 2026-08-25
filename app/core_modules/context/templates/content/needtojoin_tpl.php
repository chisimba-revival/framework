<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('link', 'htmlelements');

$header = new htmlheading();
$header->type = 1;
$error = (string) $this->getParam('error', '');
$policy = (string) $this->getParam('admissionpolicy', '');
$isAccessRequired = $error === 'accessrequired';
$header->cssClass = $isAccessRequired ? '' : 'error';
$header->str = $isAccessRequired
    ? $this->objLanguage->languageText(
        'mod_context_accessrequiredheading',
        'context',
        'Course access required'
    )
    : $this->objLanguage->code2Txt('mod_context_unabletoentercontext', 'context', NULL, 'Unable to enter [-context-]');

if ($isAccessRequired) {
    echo '<section class="chisimba-form-card chisimba-form-card--wide course-access-required"'
        . ' aria-labelledby="course-access-required-title">'
        . '<p class="student-learning-overview__eyebrow">Course admission</p>';
    $header->id = 'course-access-required-title';
}
echo $header->show();

if ($isAccessRequired) {
    $labels = array(
        'free' => 'Free account',
        'tier_1' => 'Tier 1',
        'tier_2' => 'Tier 2',
    );
    $label = isset($labels[$policy]) ? $labels[$policy] : 'Membership';
    echo '<p><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' access is required.</strong></p>';
    echo '<p>Your course enrolment and role have not changed. Choose another course or return to My Learning.</p>';
} else {
    echo '<p>'.$this->objLanguage->code2Txt('mod_context_unabletoenterinfo', 'context', NULL, 'The [-context-] you tried to enter either does not exist, or is private with access restricted to members only.').'</p>';
}


if (!$isAccessRequired) {
    $objNav = $this->getObject('contextadminnav', 'contextadmin');
    $str = $this->objLanguage->languageText('word_browse', 'glossary', 'Browse').': '.$objNav->getAlphaListingAjax();
    $str .= '<div id="browsecontextcontent"></div>';
    $str .= $this->getJavaScriptFile('contextbrowser.js');
    echo $str;
}



$link = new link($isAccessRequired
    ? $this->uri(NULL, 'mylearning')
    : $this->uri(NULL, '_default'));
$link->link = $isAccessRequired
    ? $this->objLanguage->languageText('mod_context_backtomylearning', 'context', 'Back to My Learning')
    : $this->objLanguage->languageText('phrase_backhome', 'system', 'Back to home');
$link->cssClass = $isAccessRequired ? 'button' : '';

echo '<p><br />'.$link->show().'</p>';
if ($isAccessRequired) {
    echo '</section>';
}
?>
