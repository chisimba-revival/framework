<?php
$e=static function($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$s=isset($contextAuthorSnapshot)&&is_array($contextAuthorSnapshot)?$contextAuthorSnapshot:array();
$c=isset($s['context'])&&is_array($s['context'])?$s['context']:array();
$cc=isset($c['contextcode'])?(string)$c['contextcode']:''; $title=isset($c['title'])?(string)$c['title']:$cc;
$authors=isset($s['authors'])&&is_array($s['authors'])?$s['authors']:array();
$available=isset($s['availableAuthors'])&&is_array($s['availableAuthors'])?$s['availableAuthors']:array();
$owner=isset($s['ownerUserId'])?(string)$s['ownerUserId']:''; $can=!empty($s['canManageRoster']);
$csrf=isset($contextAuthorCsrf)?(string)$contextAuthorCsrf:'';
$msg=isset($contextAuthorMessage)?(string)$contextAuthorMessage:''; $err=isset($contextAuthorError)?(string)$contextAuthorError:'';
$t=function($k){return $this->objLanguage->code2Txt($k,'contextadmin');};
$ul=static function(array $u){$n=trim((isset($u['firstName'])?$u['firstName']:'').' '.(isset($u['surname'])?$u['surname']:''));$un=isset($u['username'])?(string)$u['username']:'';return $n!==''?$n.' ('.$un.')':$un;};
$icons=$this->getObject('iconservice','ui');
$plus=$icons->render('plus',array('decorative'=>true,'class'=>'chisimba-action-icon'));
$swap=$icons->render('arrow-right-left',array('decorative'=>true,'class'=>'chisimba-action-icon'));
$minus=$icons->render('user-minus',array('decorative'=>true,'class'=>'chisimba-action-icon'));
?>
<div class="chisimba-workspace contextadmin-authors">
<h1><?php echo $e($t('mod_contextadmin_manageauthors')); ?>: <?php echo $e($title); ?></h1>
<p><?php echo $e($t('mod_contextadmin_authorshelp')); ?></p>
<?php if($msg!==''): ?><div class="success"><?php echo $e($t('mod_contextadmin_authors_'.$msg)); ?></div><?php endif; ?>
<?php if($err!==''): ?><div class="error"><?php echo $e($t('mod_contextadmin_authors_'.$err)); ?></div><?php endif; ?>
<section class="chisimba-form-section"><h2><?php echo $e($t('mod_contextadmin_authors')); ?></h2>
<table><thead><tr><th><?php echo $e($this->objLanguage->languageText('word_name')); ?></th><th><?php echo $e($t('mod_contextadmin_authorrole')); ?></th><?php if($can): ?><th><?php echo $e($this->objLanguage->code2Txt('mod_contextadmin_actions','contextadmin',null,'Actions')); ?></th><?php endif; ?></tr></thead><tbody>
<?php foreach($authors as $a): $id=isset($a['userId'])?(string)$a['userId']:''; $isOwner=$id!==''&&$id===$owner; ?>
<tr><td><?php echo $e($ul($a)); ?></td><td><?php echo $e($t($isOwner?'mod_contextadmin_owner':'mod_contextadmin_author')); ?></td>
<?php if($can): ?><td><div class="chisimba-form-actions"><?php if(!$isOwner): ?>
<form method="post" action="<?php echo $this->uri(array('action'=>'transferowner'),'contextadmin'); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>"><input type="hidden" name="contextcode" value="<?php echo $e($cc); ?>"><input type="hidden" name="userid" value="<?php echo $e($id); ?>"><button class="button chisimba-button-secondary" type="submit"><?php echo $swap; ?><span><?php echo $e($t('mod_contextadmin_transferownership')); ?></span></button></form>
<form method="post" action="<?php echo $this->uri(array('action'=>'removeauthor'),'contextadmin'); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>"><input type="hidden" name="contextcode" value="<?php echo $e($cc); ?>"><input type="hidden" name="userid" value="<?php echo $e($id); ?>"><button class="button chisimba-button-secondary" type="submit"><?php echo $minus; ?><span><?php echo $e($t('mod_contextadmin_removeauthor')); ?></span></button></form>
<?php endif; ?></div></td><?php endif; ?></tr><?php endforeach; ?>
</tbody></table></section>
<?php if($can): ?><section class="chisimba-form-section"><h2><?php echo $e($t('mod_contextadmin_addauthor')); ?></h2>
<p><?php echo $e($t('mod_contextadmin_transferownershiphelp')); ?></p>
<?php if($available): ?><form method="post" action="<?php echo $this->uri(array('action'=>'addauthor'),'contextadmin'); ?>"><input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>"><input type="hidden" name="contextcode" value="<?php echo $e($cc); ?>"><label><?php echo $e($t('mod_contextadmin_selectauthor')); ?> <select name="userid" required><?php foreach($available as $a): ?><option value="<?php echo $e(isset($a['userId'])?$a['userId']:''); ?>"><?php echo $e($ul($a)); ?></option><?php endforeach; ?></select></label><div class="chisimba-form-actions"><button class="button" type="submit"><?php echo $plus; ?><span><?php echo $e($t('mod_contextadmin_addauthor')); ?></span></button></div></form><?php else: ?><p><?php echo $e($t('mod_contextadmin_noavailableauthors')); ?></p><?php endif; ?></section><?php endif; ?>
<div class="chisimba-form-actions">
<a class="button chisimba-button-secondary" href="<?php echo $this->uri(array('action'=>'controlpanel'), 'context'); ?>"><?php echo $e(ucwords($t('mod_contextadmin_coursecontrolpanel'))); ?></a>
</div>
</div>
