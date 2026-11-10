<?php
/**
 * Canonical user country policy.
 *
 * Country codes and names come from utilities/countries. The site country is
 * listed first and used as the default for a new user; stored data is preserved.
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
        $this->countries = $catalogue->getCountryOptions();
        $this->defaultCountry = $catalogue->getDefaultCountry();
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
