<?php
/**
 * Visual administration template for the public prelogin page.
 *
 * @category  Chisimba
 * @package   prelogin
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License
 * @link      https://github.com/chisimba-revival/framework
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$text = function ($key) {
    return $this->objLanguage->languageText($key, 'prelogin');
};
$globalText = function ($key) {
    return $this->objLanguage->languageText($key);
};

$previewUrl = $this->uri(array('visitorpreview' => '1'), 'prelogin');
$editingOffUrl = $this->uri(null, 'prelogin');
$addUrl = $this->uri(array('action' => 'addblock'), 'prelogin');
$updateUrl = $this->uri(array('action' => 'update'), 'prelogin');

$renderBlock = function ($block, $side) use ($escape) {
    if ($block['isblock'] == $this->TRUE) {
        $blockType = $side === 'middle' ? 'none' : NULL;
        return $this->objBlocks->showBlock(
            $block['blockname'],
            $block['blockmodule'],
            $blockType
        );
    }

    $content = html_entity_decode($block['content'], ENT_QUOTES);
    if ($side === 'middle') {
        return '<div class="currentstory"><div class="storytitle"><h3>'
            . $escape($block['title'])
            . '</h3></div><div class="abstract">'
            . $content
            . '</div></div>';
    }

    $featureBox = $this->newObject('featurebox', 'navigation');
    return $featureBox->show($block['title'], $content);
};

$renderActions = function ($block) use ($escape, $globalText) {
    $id = $block['id'];
    $links = array(
        '<a href="' . $this->uri(array(
            'action' => 'moveup',
            'id' => $id,
        ), 'prelogin') . '">' . $escape($globalText('phrase_moveup')) . '</a>',
        '<a href="' . $this->uri(array(
            'action' => 'movedown',
            'id' => $id,
        ), 'prelogin') . '">' . $escape($globalText('phrase_movedown')) . '</a>',
        '<a href="' . $this->uri(array(
            'action' => 'editblock',
            'id' => $id,
        ), 'prelogin') . '">' . $escape($globalText('word_edit')) . '</a>',
        '<a href="' . $this->uri(array(
            'action' => 'delete',
            'id' => $id,
        ), 'prelogin') . '" data-prelogin-delete="1">'
            . $escape($globalText('word_delete')) . '</a>',
    );

    return implode('<span aria-hidden="true"> · </span>', $links);
};

$columns = array(
    'left' => $text('mod_prelogin_leftbar'),
    'middle' => $text('mod_prelogin_middle'),
    'right' => $text('mod_prelogin_rightbar'),
);

$submitMessage = '';
if ($this->getParam('change') === '2') {
    $message = $this->newObject('timeoutMessage', 'htmlelements');
    $message->setMessage($text('mod_prelogin_success'));
    $submitMessage = $message->show();
}

$deletePrompt = $escape($globalText('phrase_delete'));
$style = <<<'CSS'
<style>
.prelogin-editor { max-width: 1380px; margin: 0 auto; padding: 0 1rem 2rem; }
.prelogin-editor__switch { margin: 1rem 0; }
.prelogin-editor__switch a,
.prelogin-editor__button { display: inline-block; padding: .65rem 1rem; border-radius: .5rem; background: var(--color-primary, #087bc1); color: #fff; text-decoration: none; border: 0; cursor: pointer; }
.prelogin-editor__preview { margin: 1rem 0 1.5rem; padding: 1rem; border: 1px solid #ccd6e0; border-radius: .75rem; background: #fff; }
.prelogin-editor__preview iframe { width: 100%; min-height: 680px; border: 1px solid #ccd6e0; border-radius: .5rem; background: #fff; pointer-events: none; }
.prelogin-editor__help { margin: .25rem 0 1rem; color: #536273; }
.prelogin-editor__columns { display: grid; grid-template-columns: minmax(13rem, 1fr) minmax(24rem, 2.4fr) minmax(13rem, 1fr); gap: 1rem; align-items: start; }
.prelogin-editor__column { min-width: 0; padding: .75rem; border: 1px dashed #8ea4b8; border-radius: .75rem; background: #f5f8fb; }
.prelogin-editor__column h2 { margin-top: 0; font-size: 1.1rem; }
.prelogin-editor__block { position: relative; margin: 0 0 .75rem; padding: 2.65rem .5rem .5rem; border: 2px solid #087bc1; border-radius: .65rem; background: #fff; overflow: hidden; }
.prelogin-editor__block--hidden { border-style: dashed; opacity: .72; }
.prelogin-editor__tools { position: absolute; inset: 0 0 auto; display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; padding: .45rem .55rem; background: #e7f2fa; font-size: .88rem; }
.prelogin-editor__tools label { margin-right: auto; }
.prelogin-editor__empty { padding: 1rem; border: 1px dashed #aab8c5; border-radius: .5rem; color: #536273; background: #fff; }
.prelogin-editor__footer { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; }
@media (max-width: 900px) {
  .prelogin-editor__columns { grid-template-columns: 1fr; }
  .prelogin-editor__preview iframe { min-height: 520px; }
}
</style>
CSS;
$this->appendArrayVar('headerParams', $style);

$script = '<script>(function(){document.addEventListener("click",function(event){'
    . 'var link=event.target.closest("[data-prelogin-delete]");'
    . 'if(link&&!window.confirm("' . addslashes($deletePrompt) . '"))'
    . '{event.preventDefault();}});})();</script>';
$this->appendArrayVar('headerParams', $script);
?>
<main class="prelogin-editor">
    <div class="prelogin-editor__switch">
        <a href="<?php echo $editingOffUrl; ?>">
            <?php echo $escape($this->objLanguage->languageText(
                'mod_context_turneditingoff',
                'context'
            )); ?>
        </a>
    </div>

    <h1><?php echo $escape($text('mod_prelogin_mainheader')); ?></h1>
    <?php echo $submitMessage; ?>

    <section class="prelogin-editor__preview" aria-labelledby="visitor-preview-heading">
        <h2 id="visitor-preview-heading">
            <?php echo $escape($text('mod_prelogin_visitorpreview')); ?>
        </h2>
        <p class="prelogin-editor__help">
            <?php echo $escape($text('mod_prelogin_visitorpreviewhelp')); ?>
        </p>
        <iframe credentialless referrerpolicy="same-origin"
            src="<?php echo $escape($previewUrl); ?>"
            title="<?php echo $escape($text('mod_prelogin_visitorpreview')); ?>"></iframe>
    </section>

    <form method="post" action="<?php echo $updateUrl; ?>">
        <h2><?php echo $escape($text('mod_prelogin_editlayout')); ?></h2>
        <p class="prelogin-editor__help">
            <?php echo $escape($text('mod_prelogin_editlayouthelp')); ?>
        </p>
        <div class="prelogin-editor__columns">
            <?php foreach ($columns as $side => $heading) : ?>
                <section class="prelogin-editor__column">
                    <h2><?php echo $escape($heading); ?></h2>
                    <?php $placedBlocks = $this->objPLBlocks->getBlocks($side); ?>
                    <?php if (empty($placedBlocks)) : ?>
                        <div class="prelogin-editor__empty">
                            <?php echo $escape($text('mod_prelogin_noblocks')); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($placedBlocks as $block) : ?>
                        <?php $visible = $block['visible'] == $this->TRUE; ?>
                        <article class="prelogin-editor__block<?php echo $visible ? '' : ' prelogin-editor__block--hidden'; ?>">
                            <div class="prelogin-editor__tools">
                                <label>
                                    <input type="checkbox"
                                        name="<?php echo $escape($block['id']); ?>_vis"
                                        <?php echo $visible ? 'checked' : ''; ?>>
                                    <?php echo $escape($block['title']); ?>
                                </label>
                                <?php echo $renderActions($block); ?>
                            </div>
                            <?php if ($visible) : ?>
                                <?php echo $renderBlock($block, $side); ?>
                            <?php else : ?>
                                <p><?php echo $escape($text('mod_prelogin_hiddenblock')); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="prelogin-editor__footer">
            <button class="prelogin-editor__button" type="submit">
                <?php echo $escape($globalText('word_update')); ?>
            </button>
            <a class="prelogin-editor__button" href="<?php echo $addUrl; ?>">
                <?php echo $escape($text('mod_prelogin_addblock')); ?>
            </a>
        </div>
    </form>
</main>
