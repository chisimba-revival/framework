<?php
/** Visible teaching roles must retain the system-text abstraction. */
$root=dirname(__DIR__,3);$page=file_get_contents(dirname(__DIR__).'/templates/page/page_template.php');$context=file_get_contents($root.'/core_modules/context/register.conf');
$checks=array(
 'banner uses abstract singular role'=>str_contains($page,"code2Txt(\n                'word_lecturer'")&&str_contains($page,"'[-author-]'"),
 'course membership help uses plural token'=>str_contains($context,'Add or remove students, [-authors-] and guests.'),
 'ownership actions use plural token'=>str_contains($context,'Manage [-authors-] and ownership')&&str_contains($context,'Assign [-authors-] or transfer ownership'),
);
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}}echo "PASS: visible teaching-role labels use configurable system text.\n";
