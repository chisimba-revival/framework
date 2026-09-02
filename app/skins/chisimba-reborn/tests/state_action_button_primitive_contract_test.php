<?php
/**
 * Verify the shared state-action button keeps state separate from its action.
 *
 * @author Derek Keats
 * @package skin
 */
$stylesheet=file_get_contents(dirname(__DIR__).'/stylesheet.css');
$checks=array(
    'neutral state-action surface'=>str_contains($stylesheet,'.chisimba-state-action {')&&str_contains($stylesheet,'background: var(--chisimba-surface);'),
    'online state icon'=>str_contains($stylesheet,'.chisimba-state-action--online .chisimba-icon')&&str_contains($stylesheet,'color: var(--chisimba-success);'),
    'offline state icon'=>str_contains($stylesheet,'.chisimba-state-action--offline .chisimba-icon')&&str_contains($stylesheet,'color: var(--chisimba-danger);')
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}echo "PASS: $name\n";}
