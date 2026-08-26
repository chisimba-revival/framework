<?php

/**
 * Class cssmenu extends ChisimbaObject.
 * @package toolbar
 * @filesource
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
        die("You cannot view this page directly");
}

/**
 * Class for creating and displaying a menu using css style sheets.
 *
 * @author Megan Watson
 * @copyright (c)2004 UWC
 * @package toolbar
 * @version 1
 */
class cssmenu extends ChisimbaObject {

        public $menu = array();

        /**
         * Method to construct the class
         */
        public function init() {
                $this->objLanguage = $this->getObject('language', 'language');
                $this->objSkin = $this->getObject('skin', 'skin');
                $this->loadClass('link', 'htmlelements');
                $this->iconService = $this->getObject('iconservice', 'ui');
                $this->moduleIconResolver = $this->getObject('moduleiconresolver', 'modulecatalogue');
                //$this->objLayer = $this->loadClass('layer','htmlelements');
        }

        /**
         * Method to build the menu in html for display purposes.
         *
         * @param string $iconPath The path to the icons within the skins
         * icons folder. Default: false.
         *
         * @return string $menu The menu
         */
        public function show() {
                /* CHISIMBA_TOOLBAR_MANIFEST_ICONS: register.conf is authoritative. */

                $str = '<ul id="menuList" class="adxm">'; //this is not using this javascript menu. its using the css one
                foreach ($this->menu as $key => $item) {
                        $objLink = new link('#');
                        $objLink->link = $key . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        $str.='<li id="'.strtolower($key).'" class="navigation-list">' . $objLink->show() . '<ul class="inner-menu" >' . "\n";
                        $counter = 1;
                        $numitems = (is_countable($item) ? count($item) : 0);
                        foreach ($item as $link => $val) {
                                $params = NULL;
                                if (is_array($val) && isset($val['module'], $val['label'])) {
                                        $link = $val['module'];
                                        $params = isset($val['params']) ? $val['params'] : NULL;
                                        $val = $val['label'];
                                }
                                $icon = $this->moduleIconResolver->render(
                                        $link,
                                        '',
                                        'mainmenu-icon'
                                );

                                $objLink = new link($this->uri($params, $link));
                                $objLink->link = $icon . '<div class="menulinktext">' . $val . '</div>';

                                $valLink = $objLink->show();

                                $cssclass = '';

                                if ($counter == 1) {
                                        $cssclass = 'first';
                                } else if ($counter == $numitems) {
                                        $cssclass = 'last';
                                }

                                $str.='<li class="' . $cssclass . '">' . $valLink . "</li>\r\n";
                                $counter++;
                        }
                        $str.="</ul></li>\n";
                }
                $str .="</ul>";

                return $str;
        }

        /**
         * Method to add a menu heading.
         *
         * @param string $str Name of the menu header
         * @return
         */
        public function addHeader($str) {
                if (!empty($str)) {
                        if (array_key_exists($str, $this->menu)) {
                                
                        } else {
                                $this->menu[$str] = array();
                        }
                }
        }

        /**
         * Method to add a menu item under a menu heading.
         *
         * @param string $menuhead Name of the heading under which to place the item.
         * @param string $str The menu item.
         * @return
         */
        public function addMenuItem($menuhead, $str, $link = '#', $params = NULL) {
                if (!empty($str)) {
                        if (array_key_exists($menuhead, $this->menu)) {
                                $this->menu[$menuhead][] = array(
                                        'module' => $link,
                                        'label' => $str,
                                        'params' => $params,
                                );
                        }
                }
        }

}

?>
