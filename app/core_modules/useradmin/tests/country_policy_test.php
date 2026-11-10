<?php
/** Country ordering and stored-value regression checks. */
class ChisimbaObject
{
    public $services;
    public function getObject($name, $module) { return $this->services[$name]; }
    public function loadClass($name, $module) {}
}
$GLOBALS['kewl_entry_point_run'] = true;
require dirname(__DIR__, 2) . '/utilities/classes/countries_class_inc.php';
require dirname(__DIR__) . '/classes/countrypolicy_class_inc.php';
function expectCountry($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function countryPolicyFor($default) {
    $policy = new countrypolicy();
    $config = new class($default) {
        public $country;
        public function __construct($country) { $this->country = $country; }
        public function getCountry() { return $this->country; }
    };
    $catalogue = new countries();
    $catalogue->services = array('altconfig' => $config);
    $catalogue->init();
    $policy->services = array('countries' => $catalogue);
    $policy->init();
    return $policy;
}
$policy = countryPolicyFor('za');
expectCountry(array_key_first($policy->getCountries()) === 'ZA', 'Site country first, remaining names alphabetical, codes preserved.');
expectCountry($policy->normalise('', true) === 'ZA', 'New users default to site country.');
expectCountry($policy->normalise('DE', true) === 'DE', 'Existing country remains unchanged.');
expectCountry($policy->normalise('') === '', 'Existing unspecified country remains blank.');
$policy = countryPolicyFor('invalid');
$actual = $policy->getCountries(); $sorted = $actual; asort($sorted, SORT_NATURAL | SORT_FLAG_CASE);
expectCountry($actual === $sorted && count($actual) === 239, 'Invalid site default leaves complete alphabetical list.');
expectCountry($policy->getDefaultCountry() === '', 'Invalid default is not selectable.');
echo "PASS: country order, defaults and stored values\n";

$catalogue = $policy->services['countries'];
$names = array('ZA'=>'Afrique du Sud', 'DE'=>'Allemagne', 'FR'=>'France');
$catalogue->objConfig->country = 'FR';
expectCountry(array_keys($catalogue->getCountryOptions($names)) === array('FR','ZA','DE'), 'Translated labels retained with site preference first.');
$catalogue->objConfig->country = 'DE';
expectCountry(array_key_first($catalogue->getCountryOptions($names)) === 'DE', 'Preference resolved per call, not cached across sites.');
expectCountry(count($catalogue->getCountries()) === 239, 'Presentation ordering never mutates catalogue.');
echo "PASS: translated options and changing site preference\n";

// Exercise the legacy renderer's option order and explicit saved selection.
class dropdown
{
    private $options = array();
    private $selected = null;
    public function __construct($name) {}
    public function addOption($code, $label) { $this->options[$code] = $label; }
    public function setSelected($code) { $this->selected = $code; }
    public function show() { return array($this->options, $this->selected); }
}
$catalogue->objConfig->country = 'ZA';
list($options, $selected) = $catalogue->getDropDown('country', 'DE');
expectCountry(array_key_first($options) === 'ZA' && $selected === 'DE', 'Legacy dropdown shares ordering and preserves saved selection.');
$rest = $catalogue->getCountryOptions(); unset($rest['ZA']);
$sorted = $rest; asort($sorted, SORT_NATURAL | SORT_FLAG_CASE);
expectCountry($rest === $sorted, 'Remaining country names alphabetical.');
echo "PASS: legacy dropdown and alphabetical remainder\n";
