<?php
$e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$t = fn($key) => $this->objLanguage->code2Txt('mod_contextadmin_wizard_'.$key, 'contextadmin');
$outcomes = $wizardStep === 'outcomes';
$stepAction = $outcomes ? 'step3' : 'step4';
$saveAction = $outcomes ? 'savestep3' : 'savestep4';
$blocked = in_array($wizardError, ['stale','missing','save'], true);
$scalarStrings = static fn($values) => is_array($values) ? array_filter($values, 'is_string') : [];
echo '<div class="chisimba-wizard-heading"><h1>'.$e($wizardState['course']['title']).' — '.$e($t($wizardStep)).'</h1>';
if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('help')) echo $this->getObject('contextualhelp','help')->show('contextadmin','wizard-final',true);
echo '</div><p>'.$e($t($outcomes ? 'outcomes_intro' : 'plugins_intro')).'</p>';
if ($wizardSaved) echo '<p role="status">'.$e($t('saved')).'</p>';
if ($wizardError !== '') echo '<p class="error" role="alert">'.$e($t('error_'.$wizardError)).'</p>';
if ($blocked) echo '<p><a href="'.$e($this->uri(['action'=>$stepAction,'mode'=>$mode])).'" target="_blank" rel="noopener">'.$e($t('open_current')).'</a></p>';
echo '<form method="post" action="'.$e($this->uri(['action'=>$saveAction])).'" class="chisimba-form-card chisimba-form-card--wide">';
foreach (['contextCode'=>$contextCode,'mode'=>$mode,'revision'=>$wizardRevision,'csrf_token'=>$wizardCsrf] as $key=>$value) echo '<input type="hidden" name="'.$key.'" value="'.$e($value).'">';
if ($outcomes) {
    $existing = $wizardDraft === null ? array_column($wizardState['rows'],'learningoutcome','id') : $scalarStrings($wizardDraft['existing'] ?? []);
    $removed = $scalarStrings($wizardDraft['remove'] ?? []);
    $new = $wizardDraft === null ? [''] : array_slice(array_values($scalarStrings($wizardDraft['new'] ?? [''])),0,20);
    $position = 0;
    foreach (['outcomes'=>$existing,'newoutcomes'=>$new] as $group=>$values) {
        foreach ($values as $id=>$text) {
            ++$position;
            echo '<fieldset class="chisimba-form-field"><legend>'.$e($t($group === 'outcomes' ? 'outcome' : 'new_outcome')).' '.$position.'</legend>';
            $editor = $this->newObject('htmlarea','htmlelements');
            $editor->name = $group.'['.$id.']'; $editor->cssId = 'wizard_outcome_'.$position;
            $editor->height = '200px'; $editor->width = '100%'; $editor->value = $text; $editor->setMCQToolBar();
            echo $editor->show();
            if ($group === 'outcomes') echo '<label><input type="checkbox" name="remove[]" value="'.$e($id).'"'.(in_array($id,$removed,true)?' checked':'').'> '.$e($t('remove')).'</label>';
            echo '</fieldset>';
        }
    }
    if (count($new) < 20) echo '<button type="submit" name="operation" value="add" class="button chisimba-button-secondary"'.($blocked?' disabled':'').'>'.$e($t('add')).'</button>';
} else {
    $selected = $wizardDraft === null ? array_column($wizardState['rows'],'moduleid') : $scalarStrings($wizardDraft);
    usort($plugins, static fn($a,$b)=>strcasecmp($a['title'],$b['title']));
    foreach ($plugins as $plugin) {
        echo '<div class="chisimba-form-field"><label><input type="checkbox" name="plugins[]" value="'.$e($plugin['module_id']).'"'.(in_array($plugin['module_id'],$selected,true)?' checked':'').'> '.$e($plugin['title']).'</label><p>'.$e(html_entity_decode(strip_tags($plugin['description']), ENT_QUOTES, 'UTF-8')).'</p></div>';
    }
}
echo '<div class="chisimba-form-actions"><a class="button chisimba-button-secondary" href="'.$e($this->uri(['action'=>$outcomes?'step2':'step3','mode'=>$mode])).'">'.$e($t('back')).'</a> <button type="submit" name="operation" value="save" class="button"'.($blocked?' disabled':'').'>'.$e($t($outcomes?'save_next':'save_plugins')).'</button></div></form>';
