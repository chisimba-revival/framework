<?php

/**
 * Language Config class for chisimba.
 *
 * Provides language setup properties,
 * using the native Chisimba translation service and existing
 * all language table layouts.
 * Setup all locales
 * Use the canonical database connection for language-item maintenance
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @category  Chisimba
 * @package   language
 * @author    Prince Mbekwa <pmbekwa@uwc.ac.za>
 * @copyright 2007 Prince Mbekwa
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 * @see
 */

require_once __DIR__.'/nativetranslation.php';
/** Shared native lookup/store composition. Author: Derek Keats <derek@dkeats.com>. */
class languageConfig extends ChisimbaObject
{
    public $lang;
    public $langAdmin;
    public function init() { $this->langAdmin=$this->getObject('translationstore','language'); }
    public function setup()
    {
        if($this->lang===null){
            $this->lang=new ChisimbaTranslation($this->langAdmin);
            $this->lang->setPageID($this->getParam('module')?:'system');
        }
        return $this->lang;
    }
    public function getLangAdmin() { return $this->langAdmin; }
}
