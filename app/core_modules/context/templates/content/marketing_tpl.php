<?php
/** Shareable public course presentation. @author Derek Keats */
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8',false);
$lang=$this->getObject('language','language');$t=static fn($k)=>ucfirst($lang->code2Txt('mod_context_'.$k,'context'));
if ($this->getVar('marketingUnavailable')) { echo '<p>'.$e($t('marketing_unavailable')).'</p>'; return; }
$course=$this->getVar('marketingCourse');$page=$this->getVar('marketingPage');$content=$page['content'];
$action=$this->getObject('coursecatalogue','context')->marketingAction($course);
$image=$this->getObject('contextimage','context')->getContextImage($course['contextcode']);
$url=$this->uri(array('action'=>'marketing','contextcode'=>$course['contextcode']),'context');
$root=rtrim($this->getObject('altconfig','config')->getItem('KEWL_SITE_ROOT'),'/').'/';
$absolute=static fn($v)=>preg_match('~^https?://~',$v)?$v:(str_starts_with($v,'/') ? preg_replace('~^(https?://[^/]+).*$~','$1',$root).$v : $root.ltrim($v,'/'));
if ($page['published']) {
    $this->setVar('og_title',$course['title']);
    $this->setVar('og_content',mb_substr($content['introduction'],0,250));
    if ($image) $this->setVar('og_image',$absolute($image));
    $this->appendArrayVar('headerParams','<meta property="og:type" content="website"><meta property="og:url" content="'.$e($absolute($url)).'">');
    $this->appendArrayVar('headerParams','<link rel="canonical" href="'.$e($absolute($url)).'">');
} else $this->appendArrayVar('headerParams','<meta name="robots" content="noindex,nofollow">');
$cta=static function() use($action,$e) {
    if (!empty($action['hint'])) echo '<p class="course-card__access-detail">'.$e($action['hint']).'</p>';
    if (($action['type'] ?? '')==='notice') echo '<p>'.$e($action['message']).'</p>';
    else echo '<a class="button chisimba-button-primary" href="'.$e($action['url']).'">'.$e($action['label']).'</a>';
};
?>
<article class="chisimba-form-page"><div class="chisimba-form-card chisimba-form-card--wide">
<?php if($this->getVar('marketingManage')): ?><p><a class="button chisimba-button-secondary" href="<?php echo $e($this->uri(array('action'=>'editmarketing','contextcode'=>$course['contextcode']),'context')); ?>"><?php echo $e($t('marketing_edit')); ?></a><?php if(!$page['published']) echo ' '.$e($t('marketing_draft')); ?></p><?php endif; ?>
<header class="chisimba-course-hero"><div><h1><?php echo $e($course['title']); ?></h1><p><?php echo nl2br($e($content['introduction'])); ?></p><?php $cta(); ?></div><?php if($image): ?><img src="<?php echo $e($image); ?>" alt=""><?php endif; ?></header>
<?php if(trim($course['about'] ?? '')!==''): ?><section class="chisimba-form-section"><h2><?php echo $e($t('marketing_description')); ?></h2><p><?php echo nl2br($e(trim(html_entity_decode(strip_tags(preg_replace('~<(?:br\s*/?|/p|/div)>~i', "\n", $course['about'])), ENT_QUOTES, 'UTF-8')))); ?></p></section><?php endif; ?>
<?php foreach(array('audience','outcomes','outline') as $field): if(trim($content[$field])==='')continue; ?><section class="chisimba-form-section"><h2><?php echo $e($t('marketing_'.$field)); ?></h2><?php if($field==='audience'): ?><p><?php echo nl2br($e($content[$field])); ?></p><?php else: ?><ul><?php foreach(preg_split('/\R/u',$content[$field]) as $line) if(trim($line)!=='') echo '<li>'.$e($line).'</li>'; ?></ul><?php endif; ?></section><?php endforeach; ?>
<?php $embed=coursemarketingservice::videoEmbed($content['video_url']); if($embed): ?><section class="chisimba-form-section"><h2><?php echo $e($t('marketing_video')); ?></h2><iframe class="chisimba-course-video" src="<?php echo $e($embed); ?>" title="<?php echo $e($t('marketing_video')); ?>" loading="lazy" allow="fullscreen" referrerpolicy="strict-origin-when-cross-origin"></iframe></section><?php endif; ?>
<?php echo $this->getObject('authorbiographyrenderer','userdetails')->forCourse($course['contextcode']); ?>
<footer class="chisimba-form-section"><?php $cta(); ?></footer>
</div></article>
