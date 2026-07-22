<?php
/**
 * window_class_inc.php
 *
 * Native Chisimba window/dialog component.
 *
 * PHP version 8
 *
 * @category  Chisimba
 * @package   ui
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   GNU GPL version 2 or later
 */

if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

/**
 * Native window component.
 *
 * This class provides a server-side Chisimba abstraction over the browser's
 * native dialog element. Modules use the component without depending directly
 * on ExtJS or another JavaScript framework.
 *
 * @category Chisimba
 * @package  ui
 * @author   Derek Keats
 */
class window extends ChisimbaObject
{
    /**
     * Dialog element identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * Window title.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Window body content.
     *
     * @var string
     */
    protected $content = '';

    /**
     * Optional CSS width.
     *
     * @var string
     */
    protected $width = '';

    /**
     * Whether the window should initially be open.
     *
     * @var bool
     */
    protected $open = false;

    /**
     * Shared UI asset service.
     *
     * @var object
     */
    protected $objUi;

    /**
     * Initialise the component.
     *
     * @return void
     */
    public function init()
    {
        $this->id = 'chisimba-ui-window-' . uniqid();
        $this->objUi = $this->getObject('uiservice', 'ui');
    }

    /**
     * Set the element identifier.
     *
     * @param string $id Safe HTML element identifier.
     *
     * @return window
     */
    public function setId($id)
    {
        $id = preg_replace('/[^A-Za-z0-9\-_:.]/', '-', (string) $id);

        if ($id !== '') {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * Set the window title.
     *
     * @param string $title Window heading.
     *
     * @return window
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;

        return $this;
    }

    /**
     * Set the window body.
     *
     * Content is accepted as rendered Chisimba markup.
     *
     * @param string $content Rendered body content.
     *
     * @return window
     */
    public function setContent($content)
    {
        $this->content = (string) $content;

        return $this;
    }

    /**
     * Set the preferred window width.
     *
     * Accepts an integer pixel width or a safe CSS length.
     *
     * @param int|string $width Preferred width.
     *
     * @return window
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $this->width = ((int) $width) . 'px';
        } elseif (preg_match('/^[0-9.]+(px|rem|em|%|vw)$/', (string) $width)) {
            $this->width = (string) $width;
        }

        return $this;
    }

    /**
     * Control whether the dialog is initially open.
     *
     * @param bool $open Initial state.
     *
     * @return window
     */
    public function setOpen($open = true)
    {
        $this->open = (bool) $open;

        return $this;
    }

    /**
     * Render the window.
     *
     * @return string
     */
    public function show()
    {
        $this->objUi->show();

        $id = htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8');

        $style = '';
        if ($this->width !== '') {
            $style = ' style="--chisimba-ui-window-width: '
                . htmlspecialchars($this->width, ENT_QUOTES, 'UTF-8')
                . ';"';
        }

        $open = $this->open ? ' open' : '';

        return '<dialog class="chisimba-ui-window" id="' . $id . '"'
            . $style . $open . '>'
            . '<header class="chisimba-ui-window__header">'
            . '<h2 class="chisimba-ui-window__title">' . $title . '</h2>'
            . '<button type="button" class="chisimba-ui-window__close"'
            . ' data-ui-close="' . $id . '" aria-label="Close">&times;</button>'
            . '</header>'
            . '<div class="chisimba-ui-window__content">'
            . $this->content
            . '</div>'
            . '</dialog>';
    }

    /**
     * Render a button that opens this window.
     *
     * @param string $label Button label.
     *
     * @return string
     */
    public function showOpenButton($label)
    {
        $id = htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');

        return '<button type="button" class="chisimba-ui-button"'
            . ' data-ui-open="' . $id . '">' . $label . '</button>';
    }
}
