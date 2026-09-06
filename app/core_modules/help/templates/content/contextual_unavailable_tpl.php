<?php
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
?>
<div class="chisimba-state-notice"><strong><?php echo $e($this->objLanguage->languageText('mod_help_unavailable_title','help')); ?></strong><p><?php echo $e($this->objLanguage->languageText('mod_help_unavailable_body','help')); ?></p></div>
