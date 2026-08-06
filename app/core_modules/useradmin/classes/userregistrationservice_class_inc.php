<?php
/**
 * Canonical policy boundary for local user registration requests.
 *
 * Callers supply a registration request; persistence remains owned by the
 * canonical security and group services coordinated by UserProvisioningService.
 *
 * @category  Chisimba
 * @package   useradmin
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die();
}

class userregistrationservice extends ChisimbaObject
{
    private $objUserService;
    private $objUserProvisioning;

    public function init()
    {
        $this->objUserService = $this->getObject('userservice', 'security');
        $this->objUserProvisioning = $this->getObject(
            'userprovisioningservice',
            'security'
        );
    }

    /**
     * Register one student or guest account through canonical provisioning.
     *
     * Registration type expresses intake policy only. Course role membership
     * belongs to the separate enrolment workflow.
     *
     * @return array Structured result containing ok, code and identifiers.
     */
    public function register(array $request)
    {
        $type = strtolower(trim(isset($request['registrationType'])
            ? (string) $request['registrationType'] : ''));
        if (!in_array($type, array('student', 'guest'), true)) {
            return $this->result(false, 'registration_type_not_permitted');
        }

        $username = $this->resolveUsername(
            isset($request['username']) ? $request['username'] : '',
            isset($request['firstName']) ? $request['firstName'] : '',
            isset($request['surname']) ? $request['surname'] : '',
            array()
        );
        if ($username === null) {
            return $this->result(false, 'username_generation_failed');
        }
        $email = trim(isset($request['emailAddress'])
            ? (string) $request['emailAddress'] : '');
        if (!$this->objUserService->emailAvailable($email)) {
            return $this->result(false, 'email_taken');
        }

        $userId = $this->objUserService->generateUserId();
        if ($userId === null) {
            return $this->result(false, 'userid_allocation_failed');
        }

        $input = array(
            'userId' => $userId,
            'username' => $username,
            'firstName' => isset($request['firstName'])
                ? $request['firstName'] : '',
            'surname' => isset($request['surname'])
                ? $request['surname'] : '',
            'emailAddress' => $email,
            'title' => isset($request['title']) ? $request['title'] : '',
            'country' => isset($request['country']) ? $request['country'] : '',
            'cellnumber' => isset($request['cellnumber'])
                ? $request['cellnumber'] : '',
            'staffnumber' => isset($request['staffnumber'])
                ? $request['staffnumber'] : '',
            'sex' => '',
            'isActive' => true,
            'howCreated' => 'batch_user_registration',
        );
        $password = isset($request['password'])
            ? (string) $request['password'] : '';
        $created = $this->objUserProvisioning->createLocalUser(
            $input,
            $password
        );
        $created['registrationType'] = $type;
        $created['username'] = $username;
        return $created;
    }

    /**
     * Resolve a normalised, available username deterministically.
     *
     * The preferred value is used when available. On collision, the first
     * surname character is appended, followed by a sequence from 2 when
     * required. Reserved names prevent collisions inside an uncommitted batch.
     *
     * @return string|null
     */
    public function resolveUsername(
        $preferred,
        $firstName,
        $surname,
        array $reserved
    ) {
        $preferred = $this->normaliseUsername($preferred);
        $firstName = $this->normaliseUsername($firstName);
        $surname = $this->normaliseUsername($surname);
        $surnameInitial = $surname === '' ? '' : substr($surname, 0, 1);

        if ($preferred !== '') {
            if ($this->usernameIsAvailable($preferred, $reserved)) {
                return $preferred;
            }
            $base = $preferred . $surnameInitial;
        } else {
            $base = $firstName . $surnameInitial;
        }
        if ($base === '') {
            return null;
        }
        if ($this->usernameIsAvailable($base, $reserved)) {
            return $base;
        }
        for ($sequence = 2; $sequence <= 9999; $sequence++) {
            $candidate = $base . $sequence;
            if ($this->usernameIsAvailable($candidate, $reserved)) {
                return $candidate;
            }
        }
        return null;
    }

    private function normaliseUsername($value)
    {
        $value = trim((string) $value);
        if (function_exists('transliterator_transliterate')) {
            $converted = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; Lower()',
                $value
            );
            if ($converted !== false) {
                $value = $converted;
            }
        } else {
            $value = strtolower($value);
        }
        return preg_replace('/[^a-z0-9._-]+/', '', strtolower($value));
    }

    private function usernameIsAvailable($username, array $reserved)
    {
        $key = strtolower((string) $username);
        foreach ($reserved as $reservedUsername) {
            if (strtolower((string) $reservedUsername) === $key) {
                return false;
            }
        }
        return $this->objUserService->usernameAvailable($username);
    }

    private function result($ok, $code)
    {
        return array('ok' => (bool) $ok, 'code' => (string) $code);
    }
}
?>
