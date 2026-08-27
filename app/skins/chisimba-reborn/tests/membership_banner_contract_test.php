<?php
$root=dirname(__DIR__);
$template=file_get_contents($root.'/templates/page/page_template.php');
$css=file_get_contents($root.'/stylesheet.css');
$expect=static function($ok,$message){if(!$ok){fwrite(STDERR,"FAIL: {$message}\n");exit(1);}};
$expect(str_contains($template,"isSiteAdministrator()")
    &&str_contains($template,"isCurrentContextLecturer()")
    &&str_contains($template,"checkIfRegistered('membership-service')")
    &&str_contains($template,"effectiveTier(\$this->objUser->userId())")
    &&str_contains($template,"'tier_1' => 'Tier 1 member'")
    &&str_contains($template,"'purpose' => 'membership'"),
    'The authenticated site banner must derive its role or membership badge from canonical services.');
$expect(str_contains($css,'.chisimba-site-banner__status')
    &&str_contains($css,'border-radius: 999px'),
    'The account state must use one compact responsive banner badge.');
fwrite(STDOUT,"PASS: membership banner contract\n");
?>
