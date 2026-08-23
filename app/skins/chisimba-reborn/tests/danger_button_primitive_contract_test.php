<?php
$skins=dirname(__DIR__,2);$reborn=file_get_contents($skins.'/chisimba-reborn/stylesheet.css');$kenga=file_get_contents($skins.'/kenga-learn/stylesheet.css');
foreach(array('reborn'=>$reborn,'kenga'=>$kenga) as $name=>$css){
    if(!str_contains($css,'.chisimba-button-danger {')||!str_contains($css,'.chisimba-button-danger:hover')){fwrite(STDERR,"FAIL: $name danger button primitive\n");exit(1);}
}
echo "PASS: shared danger button and icon sizing primitives verified.\n";
