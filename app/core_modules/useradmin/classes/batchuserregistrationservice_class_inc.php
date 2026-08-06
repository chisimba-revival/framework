<?php
/**
 * CSV preview and ingestion coordinator for batch user registration.
 *
 * The service validates the complete file before confirmation, invokes the
 * canonical registration boundary once per valid row, and returns structured
 * completion data suitable for a future post-ingest communication handler.
 *
 * @category  Chisimba
 * @package   useradmin
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die();
}

class batchuserregistrationservice extends ChisimbaObject
{
    const MAX_BYTES = 1048576;
    const MAX_ROWS = 100;

    private $objRegistration;
    private $requiredHeaders = array(
        'username', 'firstname', 'surname', 'emailaddress',
        'registration_type', 'password'
    );
    private $optionalHeaders = array(
        'title', 'country', 'cellnumber', 'staffnumber'
    );

    public function init()
    {
        $this->objRegistration = $this->getObject(
            'userregistrationservice',
            'useradmin'
        );
    }

    public function previewCsv($path, $originalName)
    {
        if (strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION))
            !== 'csv') {
            return $this->failure('invalid_file_type');
        }
        if (!is_file($path) || !is_readable($path)) {
            return $this->failure('file_unreadable');
        }
        $size = filesize($path);
        if ($size === false || $size > self::MAX_BYTES) {
            return $this->failure('file_too_large');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return $this->failure('file_unreadable');
        }
        $headers = fgetcsv($handle);
        if (!is_array($headers)) {
            fclose($handle);
            return $this->failure('empty_file');
        }
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        $headers = array_map(array($this, 'normaliseHeader'), $headers);
        $allowed = array_merge($this->requiredHeaders, $this->optionalHeaders);
        if (count($headers) !== count(array_unique($headers))
            || count(array_diff($headers, $allowed)) > 0
            || in_array('', $headers, true)) {
            fclose($handle);
            return $this->failure('invalid_headers');
        }
        if (count(array_diff($this->requiredHeaders, $headers)) > 0) {
            fclose($handle);
            return $this->failure('missing_required_header');
        }

        $rows = array();
        $lineNumber = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if ($this->emptyRow($values)) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                return $this->failure('too_many_rows');
            }
            $values = array_pad($values, count($headers), '');
            $values = array_slice($values, 0, count($headers));
            $row = array_combine($headers, $values);
            $rows[] = $this->validateRow($row, $lineNumber);
        }
        fclose($handle);
        if (!$rows) {
            return $this->failure('empty_file');
        }

        $this->markFileDuplicates($rows, 'emailaddress', 'duplicate_email_in_file');
        $reservedUsernames = array();
        foreach ($rows as &$row) {
            if (!$row['valid']) {
                continue;
            }
            $requestedUsername = $row['data']['username'];
            $resolvedUsername = $this->objRegistration->resolveUsername(
                $requestedUsername,
                $row['data']['firstname'],
                $row['data']['surname'],
                $reservedUsernames
            );
            if ($resolvedUsername === null) {
                $row['errors'][] = 'username_generation_failed';
                $row['valid'] = false;
                continue;
            }
            $row['data']['requested_username'] = $requestedUsername;
            $row['data']['username'] = $resolvedUsername;
            $row['usernameGenerated'] = $resolvedUsername !== strtolower(
                trim($requestedUsername)
            );
            $reservedUsernames[] = $resolvedUsername;
        }
        unset($row);
        $valid = 0;
        foreach ($rows as $row) {
            if ($row['valid']) {
                $valid++;
            }
        }
        return array(
            'ok' => true,
            'code' => 'preview_ready',
            'batchId' => bin2hex(random_bytes(12)),
            'sourceName' => basename((string) $originalName),
            'rows' => $rows,
            'validCount' => $valid,
            'rejectedCount' => count($rows) - $valid,
        );
    }

    public function ingest(array $preview)
    {
        $results = array();
        $created = 0;
        $skipped = 0;
        $failed = 0;
        foreach ($preview['rows'] as $row) {
            if (empty($row['valid'])) {
                $skipped++;
                $results[] = array(
                    'line' => $row['line'],
                    'username' => $row['data']['username'],
                    'emailaddress' => $row['data']['emailaddress'],
                    'registrationType' => $row['data']['registration_type'],
                    'ok' => false,
                    'status' => 'skipped',
                    'code' => $row['errors'][0],
                );
                continue;
            }
            $data = $row['data'];
            $registered = $this->objRegistration->register(array(
                'username' => $data['username'],
                'firstName' => $data['firstname'],
                'surname' => $data['surname'],
                'emailAddress' => $data['emailaddress'],
                'registrationType' => $data['registration_type'],
                'password' => $data['password'],
                'title' => isset($data['title']) ? $data['title'] : '',
                'country' => isset($data['country']) ? $data['country'] : '',
                'cellnumber' => isset($data['cellnumber'])
                    ? $data['cellnumber'] : '',
                'staffnumber' => isset($data['staffnumber'])
                    ? $data['staffnumber'] : '',
            ));
            $ok = !empty($registered['ok']);
            $ok ? $created++ : $failed++;
            $results[] = array(
                'line' => $row['line'],
                'username' => $data['username'],
                'emailaddress' => $data['emailaddress'],
                'registrationType' => $data['registration_type'],
                'ok' => $ok,
                'status' => $ok ? 'created' : 'failed',
                'code' => isset($registered['code'])
                    ? $registered['code'] : 'create_failed',
                'userId' => isset($registered['userId'])
                    ? $registered['userId'] : null,
            );
        }

        return array(
            'ok' => $failed === 0,
            'code' => 'batch_completed',
            'batchId' => (string) $preview['batchId'],
            'sourceName' => (string) $preview['sourceName'],
            'completedAt' => date('c'),
            'createdCount' => $created,
            'skippedCount' => $skipped,
            'failedCount' => $failed,
            'results' => $results,
            'postIngest' => array(
                'event' => 'BatchUserImportCompleted',
                'status' => 'not_configured',
                'eligibleUsers' => array_values(array_filter(
                    $results,
                    array($this, 'isCreatedResult')
                )),
            ),
        );
    }

    public function normaliseHeader($header)
    {
        return strtolower(trim((string) $header));
    }

    public function isCreatedResult($result)
    {
        return is_array($result) && !empty($result['ok']);
    }

    private function validateRow(array $row, $lineNumber)
    {
        foreach ($row as $key => $value) {
            $row[$key] = trim((string) $value);
        }
        $errors = array();
        foreach (array('firstname', 'surname', 'emailaddress', 'registration_type', 'password') as $field) {
            if ($row[$field] === '') {
                $errors[] = 'missing_' . $field;
            }
        }
        if ($row['emailaddress'] !== ''
            && filter_var($row['emailaddress'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'invalid_emailaddress';
        }
        $row['registration_type'] = strtolower($row['registration_type']);
        if ($row['registration_type'] !== ''
            && !in_array($row['registration_type'], array('student', 'guest'), true)) {
            $errors[] = 'registration_type_not_permitted';
        }
        return array(
            'line' => (int) $lineNumber,
            'valid' => !$errors,
            'errors' => $errors,
            'data' => $row,
        );
    }

    private function markFileDuplicates(array &$rows, $field, $code)
    {
        $seen = array();
        foreach ($rows as $index => $row) {
            $value = strtolower($row['data'][$field]);
            if ($value === '') {
                continue;
            }
            if (isset($seen[$value])) {
                foreach (array($seen[$value], $index) as $duplicateIndex) {
                    if (!in_array($code, $rows[$duplicateIndex]['errors'], true)) {
                        $rows[$duplicateIndex]['errors'][] = $code;
                        $rows[$duplicateIndex]['valid'] = false;
                    }
                }
            } else {
                $seen[$value] = $index;
            }
        }
    }

    private function emptyRow(array $values)
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function failure($code)
    {
        return array('ok' => false, 'code' => (string) $code);
    }
}
?>
