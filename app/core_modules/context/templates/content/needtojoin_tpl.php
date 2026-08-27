<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('link', 'htmlelements');

$header = new htmlheading();
$header->type = 1;
$error = (string) $this->getParam('error', '');
$policy = (string) $this->getParam('admissionpolicy', '');
$isAccessRequired = $error === 'accessrequired';
$isLoggedIn = method_exists($this->objUser, 'isLoggedIn') && $this->objUser->isLoggedIn();
$admissionMode = (string) $this->getParam('privateadmissionmode', '');
$purchaseProduct = (string) $this->getParam('purchaseproduct', '');
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
        'private' => 'Explicit private-course',
    );
    $label = isset($labels[$policy]) ? $labels[$policy] : 'Membership';
    echo '<p><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' access is required.</strong></p>';
    if (!$isLoggedIn) {
        echo '<p>Create an account or sign in before choosing access to this course.</p>';
        echo '<div class="chisimba-form-actions">'
            . '<a class="button" href="'
            . htmlspecialchars(html_entity_decode($this->uri(array(), 'registration-service'), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            . '">Create account</a> '
            . '<a class="button chisimba-button-secondary" href="'
            . htmlspecialchars(html_entity_decode($this->uri(array('action' => 'showlogin'), 'security'), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            . '">Sign in</a></div>';
    } elseif ($policy === 'private' && $admissionMode === 'automatic_payment' && $purchaseProduct !== '') {
        $amount = (int) $this->getParam('purchaseamount', 0);
        $currency = strtoupper((string) $this->getParam('purchasecurrency', 'ZAR'));
        echo '<p>This course can admit you automatically after a confirmed payment.</p>';
        echo '<div class="chisimba-form-actions"><a class="button" href="'
            . htmlspecialchars(html_entity_decode($this->uri(array(
                'action' => 'catalogue', 'product' => $purchaseProduct,
            ), 'payment-service'), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            . '">Buy course access — ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8')
            . ' ' . number_format($amount / 100, 2) . '</a></div>';
    } elseif ($policy === 'private' && $admissionMode === 'manual_review') {
        echo '<p>This course requires approval by the admissions team before access can be granted.</p>';
    } elseif (in_array($policy, array('tier_1', 'tier_2'), true)) {
        echo '<p>Your current membership does not include this course.</p>';
        echo '<div class="chisimba-form-actions"><a class="button" href="'
            . htmlspecialchars(html_entity_decode($this->uri(array(
                'action' => 'catalogue', 'purpose' => 'membership',
            ), 'payment-service'), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            . '">View membership options</a></div>';
    } else {
        echo '<p>The required access is not currently available for purchase. Your course enrolment and role have not changed.</p>';
    }
} else {
    echo '<p>'.$this->objLanguage->code2Txt('mod_context_unabletoenterinfo', 'context', NULL, 'The [-context-] you tried to enter either does not exist, or is private with access restricted to members only.').'</p>';
}


$link = new link($isAccessRequired
    ? ($isLoggedIn ? $this->uri(NULL, 'mylearning') : $this->uri(array('action' => 'catalogue'), 'context'))
    : $this->uri(array('action' => 'catalogue'), 'context'));
$link->link = $isAccessRequired
    ? ($isLoggedIn
        ? $this->objLanguage->languageText('mod_context_backtomylearning', 'context', 'Back to My Learning')
        : $this->objLanguage->languageText('mod_context_browsecourses', 'context', 'Browse courses'))
    : $this->objLanguage->languageText('mod_context_browsecourses', 'context', 'Browse courses');
$link->cssClass = 'button';

echo '<p><br />'.$link->show().'</p>';
if ($isAccessRequired) {
    echo '</section>';
}
?>
