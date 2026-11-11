<?php
/** Course membership listing; all management decisions come from the controller. */
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$icons = $this->getObject('iconservice', 'ui');
$label = fn($key, $module, $fallback) => $this->objLanguage->code2Txt($key, $module, null, $fallback);
ob_start();
if ($this->getObject('modules','modulecatalogue')->checkIfRegistered('help')) echo $this->getObject('contextualhelp','help')->show('contextadmin','mycourses',true);
echo '<div class="chisimba-flow">';
if (!$courses) echo '<p>'.$escape($label('mod_context_youdonotbelongtocontexts','context','You do not belong to any [-contexts-]')).'</p>';
foreach ($courses as $course) {
    $code = $course['contextcode'];
    $open = $this->uri(['action'=>'joincontext','contextcode'=>$code], 'context');
    $manage = $this->uri(['action'=>'joincontext','contextcode'=>$code,'contextmodule'=>'context','contextaction'=>'controlpanel'], 'context');
    $edit = $this->uri(['action'=>'edit','contextcode'=>$code], 'contextadmin');
    $about = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($course['about'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $about = mb_strlen($about) > 240 ? preg_replace('/\s+\S*$/u', '', mb_substr($about, 0, 237)).'…' : $about;
    echo '<article class="chisimba-media-card">';
    if ($course['image']) echo '<a class="chisimba-media-card__image" href="'.$escape($open).'" aria-label="'.$escape($course['title']).'"><img src="'.$escape($course['image']).'" alt="" loading="lazy"></a>';
    else echo '<div class="chisimba-media-card__image chisimba-media-card__placeholder" aria-hidden="true">'.$icons->render('book-open').'</div>';
    echo '<div class="chisimba-media-card__body"><h2><a href="'.$escape($open).'">'.$escape($course['title']).'</a></h2><p>'.$escape($about).'</p><dl class="chisimba-metadata-row">';
    $names = array_map(static fn($person) => trim($person['firstname'].' '.$person['surname']), $course['lecturers']);
    $access = strtolower($course['access']);
    $accessKey = in_array($access, ['public','open','private'], true) ? $access : 'private';
    foreach ([
        ['users', $label('word_lecturers','system','[-authors-]'), implode(', ', $names)],
        [$accessKey === 'private' ? 'lock-keyhole' : 'globe', $label('mod_context_accessettings','context','Access settings'), $this->objLanguage->languageText('word_'.$accessKey,'system',ucfirst($accessKey))],
        ['hash', $label('mod_context_contextcode','system','[-context-] code'), $code],
    ] as [$icon, $term, $value]) {
        if ($value === '') continue;
        echo '<div><dt>'.$icons->render($icon).' '.$escape(ucfirst($term)).'</dt><dd>'.(in_array($icon, ['globe','lock-keyhole'], true) ? '<span class="chisimba-status-badge">'.$escape($value).'</span>' : $escape($value)).'</dd></div>';
    }
    echo '</dl><div class="chisimba-form-actions chisimba-form-actions--equal">';
    echo '<a class="button" href="'.$escape($open).'">'.$icons->render('arrow-right').' '.$escape($label('mod_contextadmin_opencourse','contextadmin','Open [-context-]')).'</a>';
    if ($course['canManage']) {
        echo '<a class="button chisimba-button-secondary" href="'.$escape($manage).'">'.$icons->render('settings-2').' '.$escape($label('mod_contextadmin_managecontext','contextadmin','Manage [-context-]')).'</a>';
        $editLabel = $label('mod_contextadmin_editcourse','contextadmin','Edit [-context-] settings');
        echo '<a class="button chisimba-button-secondary" href="'.$escape($edit).'" aria-label="'.$escape($editLabel).'" title="'.$escape($editLabel).'">'.$icons->render('pencil').'</a>';
    }
    echo '</div></div></article>';
}
echo '</div>';

$content = ob_get_clean();
include __DIR__.'/main.php';
