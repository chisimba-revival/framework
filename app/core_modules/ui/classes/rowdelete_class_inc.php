<?php
/** Native, confirmed row removal. Owning controllers retain all authorisation. */
if (empty($GLOBALS['kewl_entry_point_run'])) die('You cannot view this page directly');
class rowdelete extends ChisimbaObject
{
    private $loaded = false;
    public function init() {}
    public function render(array $options): string
    {
        if (!$this->loaded) {
            $this->appendArrayVar('headerParams', $this->getJavaScriptFile('js/rowdelete.js', 'ui'));
            $this->loaded = true;
        }
        $attributes = '';
        foreach (['url','id','revision','csrf','confirm','failure','success','label'] as $key) {
            $attributes .= ' data-'.$key.'="'.htmlspecialchars((string)$options[$key], ENT_QUOTES, 'UTF-8').'"';
        }
        return '<span data-row-delete><button type="button" class="chisimba-icon-button chisimba-button-danger"'.$attributes.' aria-label="'
            .htmlspecialchars($options['label'], ENT_QUOTES, 'UTF-8').'">'
            .$this->getObject('iconservice','ui')->render('trash-2')
            .'</button><span role="alert" data-row-delete-error></span></span>';
    }
    public function wrap(string $html): string
    {
        return '<div data-row-delete-list tabindex="-1"><p role="status" aria-live="polite" data-row-delete-status></p><div class="chisimba-table-wrap">'.$html.'</div></div>';
    }
}
