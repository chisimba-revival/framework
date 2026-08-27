<?php
$skins=dirname(__DIR__,2);$reborn=file_get_contents($skins.'/chisimba-reborn/stylesheet.css');
if(!str_contains($reborn,'.chisimba-button-danger {')||!str_contains($reborn,'.chisimba-button-danger:hover')){fwrite(STDERR,"FAIL: canonical danger button primitive\n");exit(1);}
if(!str_contains($reborn,'.chisimba-icon-mask {')||!str_contains($reborn,'background-color: currentColor;')||!str_contains($reborn,'mask: var(--chisimba-icon-url)')){fwrite(STDERR,"FAIL: colour-inheriting icon primitive\n");exit(1);}
echo "PASS: shared danger button and icon sizing primitives verified.\n";
