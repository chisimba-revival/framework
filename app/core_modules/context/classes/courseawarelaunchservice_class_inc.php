<?php
/**
 * Builds and validates course-aware deep-link targets.
 *
 * @category  Chisimba
 * @package   context
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

class courseawarelaunchservice extends ChisimbaObject
{
    /**
     * Wrap a module destination in the canonical course-entry journey.
     *
     * @param string $contextCode Target course code.
     * @param string $module Target module ID.
     * @param array $params Bounded scalar destination parameters.
     * @return array Standard module/params launch target.
     */
    public function target($contextCode, $module, array $params = array())
    {
        $action = isset($params['action']) ? (string) $params['action'] : '';
        unset($params['action']);
        return array(
            'module' => 'context',
            'params' => array(
                'action' => 'launchcourseactivity',
                'coursecode' => $this->identifier($contextCode),
                'targetmodule' => $this->identifier($module),
                'targetaction' => $this->identifier($action, true),
                'targetparams' => $this->encodeParams($params),
            ),
        );
    }

    /** Decode and validate a destination received by the context controller. */
    public function request($courseCode, $module, $action, $encodedParams)
    {
        return array(
            'coursecode' => $this->identifier($courseCode),
            'module' => $this->identifier($module),
            'action' => $this->identifier($action, true),
            'params' => $this->decodeParams($encodedParams),
        );
    }

    /** Encode bounded scalar parameters without creating an open redirect. */
    private function encodeParams(array $params)
    {
        $safe = array();
        foreach ($params as $key => $value) {
            $key = $this->identifier($key);
            if ($key !== '' && (is_string($value) || is_int($value) || is_float($value) || is_bool($value))) {
                $safe[$key] = (string) $value;
            }
        }
        return rtrim(strtr(base64_encode(json_encode($safe)), '+/', '-_'), '=');
    }

    /** Decode bounded scalar parameters created by encodeParams(). */
    private function decodeParams($encoded)
    {
        $encoded = trim((string) $encoded);
        if ($encoded === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $encoded)) {
            return array();
        }
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        $values = $decoded === false ? null : json_decode($decoded, true);
        if (!is_array($values) || count($values) > 20) {
            return array();
        }
        $safe = array();
        foreach ($values as $key => $value) {
            $key = $this->identifier($key);
            if ($key !== '' && is_scalar($value)) {
                $safe[$key] = mb_substr((string) $value, 0, 2048);
            }
        }
        return $safe;
    }

    /** Validate a Chisimba identifier used for routing. */
    private function identifier($value, $allowEmpty = false)
    {
        $value = trim((string) $value);
        if ($allowEmpty && $value === '') {
            return '';
        }
        return preg_match('/^[A-Za-z0-9_-]{1,128}$/', $value) ? $value : '';
    }
}
