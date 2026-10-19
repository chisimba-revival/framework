<?php
$language = $this->getObject('language', 'language');
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = static fn($key) => $e($language->code2Txt('mod_tagging_' . $key, 'tagging'));
echo '<section class="chisimba-form-card"><h1>' . $t('trytags') . '</h1>';
echo $this->getObject('contextualhelp', 'help')->show('tagging', 'suggestions');
echo '<p>' . $t('instructions') . '</p><label>' . $t('tags') . ' ';
echo $this->newObject('ajaxtags', 'tagging')->show();
echo '</label></section>';
