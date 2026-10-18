<?php

/**
 * This class converts retrieves the name of a language by providing the ISO code and also vice versa
 *
 * The original list of code was taken from a class written by Florian Breit (florian at phpws dot org):
 *  http://www.phpclasses.org/browse/file/8143.html
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
 * @see       http://www.phpclasses.org/browse/file/8143.html
 */
/**
*This is a Languagecode class
* @author    Prince Mbekwa
* @copyright (c) 200-2004 University of the Western Cape
* @Version   1
*/

/**
 *Description of the class
* This class converts retrieves the name of a language by providing the ISO code and also vice versa
*
* The original list of code was taken from a class written by Florian Breit (florian at phpws dot org):
*  http://www.phpclasses.org/browse/file/8143.html
*/
/** Native UTF-8 locale lists. Author: Derek Keats <derek@dkeats.com>. */
class languagecode extends ChisimbaObject
{
    public $objConfig;
    public $iso_639_2_tags;
    public $lan;
    private $countries=[];
    public function init()
    {
        $this->objConfig=$this->getObject('altconfig','config');
        $this->lan=strtolower($this->objConfig->getdefaultLanguageAbbrev());
        $data=json_decode(file_get_contents(__DIR__.'/../resources/locale/names.json'),true,512,JSON_THROW_ON_ERROR);
        $this->countries=$data['countries'][$this->lan]??$data['countries']['en'];
        // Callers historically edit this public codes list; retain its object shape.
        $this->iso_639_2_tags=(object)['codes'=>$data['languages']['en']];
    }
    public function getLanguage($code) { return $this->iso_639_2_tags->codes[strtolower($code)]??null; }
    public function getISO($language)
    {
        $language=strtolower($language);
        if(isset($this->iso_639_2_tags->codes[$language]))return $language;
        foreach($this->iso_639_2_tags->codes as $code=>$name)if(strtolower($name)===$language)return $code;
        return null;
    }
    public function getName($code) { return $this->countries[strtoupper($code)]??''; }
    private function escape($value) { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    public function countryListArr($country=null)
    {
        return array_map(fn($name)=>htmlentities($name,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),$this->countries);
    }
    private function select($selected,$alphabetical=false,$submit=false)
    {
        $selected=strtoupper($selected?:$this->objConfig->getCountry());$countries=$this->countries;
        if($alphabetical)asort($countries);
        $html='<select name="country"'.($alphabetical?' id="input_country" class="WCHhider"':'').($submit?' onchange="this.form.submit()"':'').'>';
        foreach($countries as $code=>$name)$html.='<option value="'.$this->escape($code).'"'.($code===$selected?' selected="selected"':'').'>'.$this->escape($name).'</option>';
        return $html.'</select>';
    }
    public function country($country=null) { return $this->select($country); }
    public function countryAlpha($country=null) { return $this->select($country,true); }
    public function dec_country() { return $this->select(null,false,true); }
    /** Explicit output conversion only; request parameters are never rewritten. */
    public function autoConv($output,$input)
    {
        if(strcasecmp($output,$input)===0)return true;
        if(!in_array(strtoupper($output),['UTF-8','ISO-8859-1','WINDOWS-1252'],true)||!in_array(strtoupper($input),['UTF-8','ISO-8859-1','WINDOWS-1252'],true))throw new InvalidArgumentException('Unsupported output encoding.');
        return ob_start(static function($buffer)use($output,$input){$value=iconv($input,$output,$buffer);if($value===false)throw new RuntimeException('Output conversion failed.');return $value;});
    }
}
