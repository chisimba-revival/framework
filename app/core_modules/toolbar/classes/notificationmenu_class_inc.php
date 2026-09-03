<?php
/** Optional notification menu; notification data remains module-owned. @author Derek Keats */
class notificationmenu extends ChisimbaObject
{
    public function show()
    {
        if (!$this->getObject('user', 'security')->isLoggedIn()
            || !$this->getObject('modules', 'modulecatalogue')->checkIfRegistered('notifications')) {
            return '';
        }
        return '<li class="toolbar-notification-item">'
            . $this->getObject('notificationtoolbar', 'notifications')->show() . '</li>';
    }
}
