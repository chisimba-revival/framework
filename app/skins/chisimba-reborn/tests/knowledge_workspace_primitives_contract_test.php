<?php
/**
 * Verify shared primitives needed by spatial knowledge workspaces.
 *
 * @author Derek Keats
 * @package skin
 */
$stylesheet=file_get_contents(dirname(__DIR__).'/stylesheet.css');
$checks=array(
    'compact button primitive'=>str_contains($stylesheet,'.chisimba-button-compact {'),
    'icon button primitive'=>str_contains($stylesheet,'.chisimba-icon-button {'),
    'small icon button density'=>str_contains($stylesheet,'.chisimba-icon-button--small {'),
    'toolbar primitive'=>str_contains($stylesheet,'.chisimba-toolbar {'),
    'popover primitive'=>str_contains($stylesheet,'.chisimba-popover {'),
    'workspace surface primitive'=>str_contains($stylesheet,'.chisimba-spatial-workspace {'),
    'collapsible drawer primitive'=>str_contains($stylesheet,'.chisimba-drawer {')&&str_contains($stylesheet,'.chisimba-drawer[aria-hidden="true"]')
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
