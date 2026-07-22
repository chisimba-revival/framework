<?php
/**
 * Native accessible Chisimba message-box component.
 *
 * @category Chisimba
 * @package ui
 * @author Derek Keats
 */
class messagebox extends ChisimbaObject
{
    public function init()
    {
    }

    /**
     * Render a semantic message.
     *
     * @param string $message
     * @param string $type info, success, warning, or error
     * @param string $title
     * @param bool $dismissible
     * @param string $id
     * @return string
     */
    public function show($message, $type = 'info', $title = '', $dismissible = false, $id = '')
    {
        $allowed = array('info', 'success', 'warning', 'error');
        if (!in_array($type, $allowed, true)) {
            $type = 'info';
        }

        $role = in_array($type, array('warning', 'error'), true) ? 'alert' : 'status';

        $message = htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8');
        $id = htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8');

        $html = '<div class="chisimba-message chisimba-message--' . $type . '"';
        $html .= ' role="' . $role . '" aria-atomic="true" data-chisimba-message';
        if ($id !== '') {
            $html .= ' id="' . $id . '"';
        }
        $html .= '><div class="chisimba-message__content">';

        if ($title !== '') {
            $html .= '<strong class="chisimba-message__title">' . $title . '</strong>';
        }

        $html .= '<div class="chisimba-message__text">' . $message . '</div></div>';

        if ($dismissible) {
            $html .= '<button type="button" class="chisimba-message__dismiss"';
            $html .= ' data-chisimba-message-dismiss aria-label="Dismiss message">';
            $html .= '<span aria-hidden="true">&times;</span></button>';
        }

        $html .= '</div>';
        return $html;
    }
}
