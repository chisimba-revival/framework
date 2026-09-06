<?php
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$icons = $this->getObject('iconservice', 'ui');
?>
<main class="chisimba-help-guide chisimba-prose">
<p class="chisimba-eyebrow"><?php echo $e($this->objLanguage->languageText('mod_help_guide_eyebrow','help')); ?></p>
<h1><?php echo $e($helpTopic['title']); ?></h1>
<p class="chisimba-help-guide__summary"><?php echo $e($helpTopic['summary']); ?></p>
<?php if (!empty($helpTopic['steps'])): ?>
<section class="chisimba-card"><h2><?php echo $e($this->objLanguage->languageText('mod_help_guide_steps','help')); ?></h2><ol>
<?php foreach ($helpTopic['steps'] as $step): ?><li><?php echo $e($step); ?></li><?php endforeach; ?>
</ol></section>
<?php endif; ?>
<?php foreach ((array)($helpTopic['sections'] ?? array()) as $section): ?>
<section><h2><?php echo $e($section['heading']); ?></h2><p><?php echo $e($section['body']); ?></p></section>
<?php endforeach; ?>
</main>
