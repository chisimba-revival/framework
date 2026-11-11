<?php
/** Public, read-only biography: no account editor or private profile fields. */
$person=$this->getVar('publicBiographyPerson');
if ($person) {
    echo $this->getObject('authorbiographyrenderer','userdetails')->person($person,true);
} else {
    echo '<p>'.htmlspecialchars($this->getObject('language','language')->languageText('mod_userdetails_bio_unavailable','userdetails'),ENT_QUOTES,'UTF-8').'</p>';
}
