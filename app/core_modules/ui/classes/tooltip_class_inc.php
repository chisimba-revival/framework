<?php
/**
 * tooltip_class_inc.php
 *
 * Native Chisimba tooltip component.
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
 * Native accessible tooltip component.
 *
 * The component renders a trigger and tooltip relationship using semantic
 * HTML and ARIA. Behaviour is provided by CSS and the common ui script.
 *
 * @category Chisimba
 * @package  ui
 * @author   Derek Keats
 */
class tooltip extends ChisimbaObject
{
    /**
     * Tooltip identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * Visible trigger content.
     *
     * @var string
     */
    protected $trigger = '';

    /**
     * Tooltip text.
     *
     * @var string
     */
    protected $text = '';

    /**
     * Shared UI service.
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
        $this->id = 'chisimba-ui-tooltip-' . uniqid();
        $this->objUi = $this->getObject('uiservice', 'ui');
    }

    /**
     * Set the tooltip identifier.
     *
     * @param string $id HTML element identifier.
     *
     * @return tooltip
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
     * Set the visible trigger markup.
     *
     * The trigger may contain rendered Chisimba markup.
     *
     * @param string $trigger Trigger markup.
     *
     * @return tooltip
     */
    public function setTrigger($trigger)
    {
        $this->trigger = (string) $trigger;

        return $this;
    }

    /**
     * Set the tooltip text.
     *
     * @param string $text Plain tooltip text.
     *
     * @return tooltip
     */
    public function setText($text)
    {
        $this->text = (string) $text;

        return $this;
    }

    /**
     * Render the tooltip.
     *
     * @return string
     */
    public function show()
    {
        $this->objUi->show();

        $id = htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($this->text, ENT_QUOTES, 'UTF-8');

        return '<span class="chisimba-ui-tooltip">'
            . '<span class="chisimba-ui-tooltip__trigger" tabindex="0"'
            . ' aria-describedby="' . $id . '">'
            . $this->trigger
            . '</span>'
            . '<span class="chisimba-ui-tooltip__content" id="' . $id . '"'
            . ' role="tooltip">'
            . $text
            . '</span>'
            . '</span>';
    }
}
