<?php
/** Atomic orchestration for Context Admin Step 1. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class coursecreationservice extends ChisimbaObject
{
    private $context;
    private $modules;

    public function init()
    {
        $this->context = $this->getObject('dbcontext', 'context');
        $this->modules = $this->getObject('modules', 'modulecatalogue');
    }

    public function create(array $request)
    {
        $code = strtolower(trim((string) ($request['contextCode'] ?? '')));
        $title = trim((string) ($request['title'] ?? ''));
        $status = (string) ($request['status'] ?? 'Published');
        $access = (string) ($request['access'] ?? 'Private');
        $accessPolicy = $this->context->normaliseAccessPolicy(
            $request['accessPolicy'] ?? null,
            true
        );
        $privateAdmissionMode = $this->context->normalisePrivateAdmissionMode(
            $request['privateAdmissionMode'] ?? null,
            true
        );
        if ($accessPolicy !== 'private') {
            $privateAdmissionMode = null;
        }
        $showComment = (string) ($request['showComment'] ?? '0');
        $alerts = (string) ($request['alerts'] ?? '0');
        $design = $this->context->validateLearningDesign(
            $request['deliveryFormat'] ?? 'standard',
            $request['navigationMode'] ?? null
        );

        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,254}$/', $code) || $code === 'root' || $title === '') {
            return $this->result(false, 'invalid_context');
        }
        if (!in_array($status, array('Published', 'Unpublished'), true)
            || !in_array($access, array('Public', 'Open', 'Private'), true)
            || $accessPolicy === false || $privateAdmissionMode === false
            || !in_array($showComment, array('0', '1'), true)
            || !in_array($alerts, array('0', '1'), true)
            || $design === false) {
            return $this->result(false, 'invalid_settings');
        }

        $cpd = !empty($request['cpdEnabled']);
        // CPD awards require an authenticated, enrolled learner identity.
        // Enforce this integrity rule here rather than trusting the form.
        if ($cpd) {
            $access = 'Private';
        }
        if ($cpd && !$this->modules->checkIfRegistered('cpd', 'cpd')) {
            return $this->result(false, 'cpd_unavailable');
        }
        if ($cpd && (!$this->validCanonicalDate($request['cpdValidFrom'] ?? '', true)
            || !$this->validCanonicalDate($request['cpdValidUntil'] ?? '', true))) {
            return $this->result(false, 'invalid_cpd_date');
        }
        if ($cpd && !empty($request['cpdValidFrom']) && !empty($request['cpdValidUntil'])
            && strcmp((string) $request['cpdValidFrom'], (string) $request['cpdValidUntil']) > 0) {
            return $this->result(false, 'invalid_cpd_date_range');
        }

        $this->context->beginTransaction();
        try {
            $created = $this->context->createContext(
                $code, $title, $status, $access, '', false, $showComment,
                $alerts, '', $design['delivery_format'], $design['navigation_mode'], false,
                $accessPolicy, $privateAdmissionMode
            );
            if (!$created) {
                throw new RuntimeException('context_create_failed');
            }

            if ($cpd) {
                $cpdService = $this->getObject('cpdservice', 'cpd');
                $cpdResult = $cpdService->recogniseContext(array(
                    'contextCode' => $code,
                    'schemeId' => $request['cpdSchemeId'] ?? '',
                    'categoryId' => $request['cpdCategoryId'] ?? '',
                    'points' => $request['cpdPoints'] ?? null,
                    'validFrom' => $request['cpdValidFrom'] ?? '',
                    'validUntil' => $request['cpdValidUntil'] ?? '',
                    'reason' => $request['cpdReason'] ?? '',
                    'actorUserId' => $request['actorUserId'] ?? ''
                ));
                if (empty($cpdResult['ok'])) {
                    throw new RuntimeException('cpd_' . (string) ($cpdResult['code'] ?? 'failed'));
                }
            }

            $this->context->commitTransaction();
        } catch (Throwable $failure) {
            $this->context->rollbackTransaction();
            return $this->result(false, $failure->getMessage());
        }

        $this->context->indexCreatedContext($code);
        return $this->result(true, 'created', $code);
    }

    private function result($ok, $code, $contextCode = null)
    {
        return array('ok' => (bool) $ok, 'code' => (string) $code, 'contextCode' => $contextCode);
    }

    private function validCanonicalDate($value, $allowEmpty)
    {
        $value = (string) $value;
        if ($allowEmpty && $value === '') { return true; }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
?>
