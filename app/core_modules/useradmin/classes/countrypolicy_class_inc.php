<?php
/**
 * Canonical user country policy.
 *
 * Country codes and names come from utilities/countries. The site country is
 * used only as the default for a new user and never overwrites stored data.
 *
 * @category  Chisimba
 * @package   useradmin
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class countrypolicy extends ChisimbaObject
{
    private $countries = array();
    private $defaultCountry = '';

    public function init()
    {
        $catalogue = $this->getObject('countries', 'utilities');
        $countries = $catalogue->getCountries();
        if (is_array($countries)) {
            $this->countries = $countries;
            ksort($this->countries, SORT_STRING);
        }

        $config = $this->getObject('altconfig', 'config');
        $candidate = strtoupper(trim((string) $config->getCountry()));
        if (array_key_exists($candidate, $this->countries)) {
            $this->defaultCountry = $candidate;
        }
    }

    public function getCountries()
    {
        return $this->countries;
    }

    public function getDefaultCountry()
    {
        return $this->defaultCountry;
    }

    public function normalise($country, $useDefault = false)
    {
        $country = strtoupper(trim((string) $country));
        if ($country === '' && $useDefault) {
            return $this->defaultCountry;
        }
        return array_key_exists($country, $this->countries) ? $country : '';
    }
}
?>
