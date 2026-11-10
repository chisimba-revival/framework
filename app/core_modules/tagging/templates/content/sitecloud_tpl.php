<?php
$language = $this->getObject('language', 'language');
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
echo '<section class="chisimba-form-card"><h1>' . $e($language->code2Txt('mod_tagging_own', 'tagging')) . '</h1>';
echo $this->getObject('contextualhelp', 'help')->show('tagging', 'suggestions');
if (!$tags) echo '<p>' . $e($language->code2Txt('mod_tagging_empty', 'tagging')) . '</p>';
else {
    echo '<ul>';
    foreach ($tags as $tag) echo '<li>' . $e($tag['meta_value']) . ' (' . (int) $tag['tag_count'] . ')</li>';
    echo '</ul>';
}
echo '</section>';
