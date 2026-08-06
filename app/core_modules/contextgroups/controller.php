<?php
/**
 * Clear and scalable course-membership management.
 *
 * Membership reads and writes use the canonical Group and Identity services.
 *
 * @category Chisimba
 * @package  contextgroups
 * @author   Derek Keats
 * @license  http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL v2 or later
 */

if (empty($GLOBALS['kewl_entry_point_run'])) {
    die();
}

class contextgroups extends controller
{
    // CHISIMBA_CONTEXTGROUPS_REBORN
    // CHISIMBA_CONTEXTGROUPS_INHERITED_PROPERTIES_FIXED
    // CHISIMBA_CONTEXTGROUPS_BULK_STUDENTS
    const BULK_PAGE_SIZE = 50;
    const CSV_MAX_BYTES = 2097152;
    const CSV_MAX_ROWS = 5000;

    private $contextStore;
    private $objGroupService;
    private $objIdentityService;
    private $languageService;
    private $securityUser;
    private $userId;

    public function init()
    {
        $this->contextStore = $this->getObject('dbcontext', 'context');
        $this->objGroupService = $this->getObject('groupservice', 'groupadmin');
        $this->objIdentityService = $this->getObject(
            'identityservice',
            'security'
        );
        $this->securityUser = $this->getObject('user', 'security');
        $this->languageService = $this->getObject('language', 'language');
        $this->userId = $this->securityUser->userId();
    }

    public function dispatch($action)
    {
        if ($this->contextStore->getContextCode() === '') {
            return $this->nextAction(null, null, '_default');
        }

        $this->setLayoutTemplate('contextgroups_layout_tpl.php');

        switch ((string) $action) {
            case 'addusers':
                return $this->addMember();

            case 'removeuser':
                return $this->removeMember();

            case 'bulkupdatestudents':
                return $this->bulkUpdateStudents();

            case 'removeallstudents':
                return $this->removeAllStudents();

            case 'importstudents':
                return $this->importStudents();

            case 'importusers':
            case 'searchforusers':
            case 'viewsearchresults':
            default:
                return $this->groupsHome();
        }
    }

    public function isValid($action, $default = true)
    {
        $action = $action === null ? '' : (string) $action;
        $allowed = array(
            '',
            'default',
            'searchforusers',
            'viewsearchresults',
            'addusers',
            'removeuser',
            'bulkupdatestudents',
            'removeallstudents',
            'importstudents',
            'importusers',
        );

        return in_array($action, $allowed, true)
            && $this->canManageMembers();
    }

    private function canManageMembers()
    {
        return $this->securityUser->isAdmin()
            || $this->securityUser->isContextLecturer(
                $this->userId,
                $this->contextStore->getContextCode()
            );
    }

    private function groupsHome()
    {
        $contextCode = $this->contextStore->getContextCode();
        $contextTitle = $this->contextStore->getTitle();
        $roles = $this->roleDefinitions();
        $groupIds = $this->contextRoleGroupIds();
        $memberSections = array();
        $membersByRole = array();
        $rolesByUser = array();

        foreach ($roles as $role => $definition) {
            $members = $this->objGroupService->getMembers($groupIds[$role]);
            $membersByRole[$role] = $members;
            $displayLimit = $role === 'student' ? 20 : 100;
            $memberSections[$role] = array(
                'label' => $definition['plural'],
                'singular' => $definition['singular'],
                'members' => array_slice($members, 0, $displayLimit),
                'count' => count($members),
                'truncated' => count($members) > $displayLimit,
            );

            foreach ($members as $member) {
                $memberUserId = $this->userIdFromRecord($member);
                if ($memberUserId === '') {
                    continue;
                }
                if (!isset($rolesByUser[$memberUserId])) {
                    $rolesByUser[$memberUserId] = array();
                }
                $rolesByUser[$memberUserId][] = $role;
            }
        }

        $search = trim((string) $this->getParam('search', ''));
        $searchResults = array();
        $searchLimited = false;
        if ($search !== '') {
            $allUsers = $this->allSystemUsers($groupIds['student']);
            $searchResults = $this->filterUsers(
                $allUsers,
                $search,
                $rolesByUser,
                100,
                $searchLimited
            );
        }

        $bulkSearch = trim((string) $this->getParam('bulksearch', ''));
        $bulkPage = $this->positiveInteger($this->getParam('bulkpage', 1), 1);
        $allBulkUsers = $this->allSystemUsers($groupIds['student']);
        $unusedLimited = false;
        $bulkUsers = $this->filterUsers(
            $allBulkUsers,
            $bulkSearch,
            $rolesByUser,
            null,
            $unusedLimited
        );
        $bulkTotal = count($bulkUsers);
        $bulkPages = max(1, (int) ceil($bulkTotal / self::BULK_PAGE_SIZE));
        $bulkPage = min($bulkPage, $bulkPages);
        $bulkOffset = ($bulkPage - 1) * self::BULK_PAGE_SIZE;
        $bulkUsers = array_slice($bulkUsers, $bulkOffset, self::BULK_PAGE_SIZE);
        $studentIds = array();
        foreach ($membersByRole['student'] as $student) {
            $studentId = $this->userIdFromRecord($student);
            if ($studentId !== '') {
                $studentIds[$studentId] = true;
            }
        }
        foreach ($bulkUsers as &$bulkUser) {
            $bulkUserId = $this->userIdFromRecord($bulkUser);
            $bulkUser['isStudent'] = isset($studentIds[$bulkUserId]);
            $bulkUser['courseRoles'] = isset($rolesByUser[$bulkUserId])
                ? $rolesByUser[$bulkUserId]
                : array();
            $bulkUser['protectLecturer'] = $bulkUserId === (string) $this->userId
                && in_array('lecturer', $bulkUser['courseRoles'], true);
        }
        unset($bulkUser);

        $membershipToken = $this->membershipToken(true);
        $currentUserId = (string) $this->userId;

        $this->setVarByRef('contextCode', $contextCode);
        $this->setVarByRef('contextTitle', $contextTitle);
        $this->setVarByRef('roles', $roles);
        $this->setVarByRef('memberSections', $memberSections);
        $this->setVarByRef('search', $search);
        $this->setVarByRef('searchResults', $searchResults);
        $this->setVarByRef('searchLimited', $searchLimited);
        $this->setVarByRef('bulkSearch', $bulkSearch);
        $this->setVarByRef('bulkUsers', $bulkUsers);
        $this->setVarByRef('bulkTotal', $bulkTotal);
        $this->setVarByRef('bulkPage', $bulkPage);
        $this->setVarByRef('bulkPages', $bulkPages);
        $this->setVarByRef('bulkOffset', $bulkOffset);
        $this->setVarByRef('membershipToken', $membershipToken);
        $this->setVarByRef('currentUserId', $currentUserId);
        $pageTexts = $this->pageTexts();
        $this->setVarByRef('pageTexts', $pageTexts);

        return 'home_tpl.php';
    }

    private function allSystemUsers($studentGroupId)
    {
        $users = array();
        $records = array_merge(
            $this->objGroupService->getMembers($studentGroupId),
            $this->objGroupService->getAvailableUsers($studentGroupId)
        );

        foreach ($records as $record) {
            $recordUserId = $this->userIdFromRecord($record);
            if ($recordUserId !== '') {
                $record['userId'] = $recordUserId;
                $users[$recordUserId] = $record;
            }
        }

        $users = array_values($users);
        usort($users, array($this, 'compareUsers'));

        return $users;
    }

    private function filterUsers(
        array $users,
        $search,
        array $rolesByUser,
        $limit,
        &$limited
    ) {
        $search = trim((string) $search);
        $matches = array();
        $limited = false;

        foreach ($users as $candidate) {
            $candidateUserId = $this->userIdFromRecord($candidate);
            if ($candidateUserId === '') {
                continue;
            }
            if ($search !== '') {
                $haystack = implode(' ', array(
                    isset($candidate['displayName'])
                        ? (string) $candidate['displayName']
                        : '',
                    isset($candidate['username'])
                        ? (string) $candidate['username']
                        : '',
                    isset($candidate['email'])
                        ? (string) $candidate['email']
                        : '',
                ));
                if (stripos($haystack, $search) === false) {
                    continue;
                }
            }

            $candidate['userId'] = $candidateUserId;
            $candidate['courseRoles'] = isset($rolesByUser[$candidateUserId])
                ? $rolesByUser[$candidateUserId]
                : array();
            $matches[] = $candidate;
        }

        if ($limit !== null && count($matches) > $limit) {
            $limited = true;
            $matches = array_slice($matches, 0, $limit);
        }

        return $matches;
    }

    private function compareUsers(array $left, array $right)
    {
        $leftName = isset($left['displayName'])
            ? (string) $left['displayName']
            : '';
        $rightName = isset($right['displayName'])
            ? (string) $right['displayName']
            : '';
        $comparison = strcasecmp($leftName, $rightName);
        if ($comparison !== 0) {
            return $comparison;
        }

        return strcasecmp(
            isset($left['username']) ? (string) $left['username'] : '',
            isset($right['username']) ? (string) $right['username'] : ''
        );
    }

    private function addMember()
    {
        $validationError = $this->mutationValidationError();
        if ($validationError !== null) {
            return $this->redirectWithError($validationError);
        }

        $role = $this->normaliseRole($this->getParam('role'));
        $targetUserId = $this->normaliseUserId($this->getParam('userid'));
        if ($role === null || $targetUserId === null) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_invalidselection')
            );
        }

        $groupIds = $this->contextRoleGroupIds();
        $desired = $this->currentRoleState(array($targetUserId), $groupIds);
        if ($role !== 'lecturer'
            && $targetUserId === (string) $this->userId
            && $desired[$targetUserId]['lecturer']) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_selflecturer')
            );
        }
        foreach (array_keys($desired[$targetUserId]) as $roleName) {
            $desired[$targetUserId][$roleName] = $roleName === $role;
        }

        $error = $this->reconcileRoleStates($desired, $groupIds);
        if ($error !== null) {
            return $this->redirectWithError($error);
        }

        return $this->nextAction(null, array(
            'message' => $this->text(
                'mod_contextgroups_msg_memberadded',
                array('ROLE' => $this->roleDefinitions()[$role]['singular'])
            ),
        ));
    }

    private function removeMember()
    {
        $validationError = $this->mutationValidationError();
        if ($validationError !== null) {
            return $this->redirectWithError($validationError);
        }

        $role = $this->normaliseRole($this->getParam('role'));
        $targetUserId = $this->normaliseUserId($this->getParam('userid'));
        if ($role === null || $targetUserId === null) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_invalidselection')
            );
        }
        if ($role === 'lecturer'
            && $targetUserId === (string) $this->userId) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_selflecturer')
            );
        }

        $groupIds = $this->contextRoleGroupIds();
        $desired = $this->currentRoleState(array($targetUserId), $groupIds);
        $desired[$targetUserId][$role] = false;
        $error = $this->reconcileRoleStates($desired, $groupIds);
        if ($error !== null) {
            return $this->redirectWithError($error);
        }

        return $this->nextAction(null, array(
            'message' => $this->text(
                'mod_contextgroups_msg_memberremoved',
                array('ROLE' => $this->roleDefinitions()[$role]['plural'])
            ),
        ));
    }

    private function bulkUpdateStudents()
    {
        $validationError = $this->mutationValidationError();
        if ($validationError !== null) {
            return $this->redirectWithError($validationError);
        }

        $listedValue = isset($_POST['listedids']) ? $_POST['listedids'] : array();
        $selectedValue = isset($_POST['studentids']) ? $_POST['studentids'] : array();
        $listedIds = $this->normaliseUserIdList($listedValue);
        $selectedIds = $this->normaliseUserIdList($selectedValue);
        if ($listedIds === null || $selectedIds === null
            || count($listedIds) > self::BULK_PAGE_SIZE) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_bulkselection')
            );
        }

        $listedMap = array_fill_keys($listedIds, true);
        foreach ($selectedIds as $selectedId) {
            if (!isset($listedMap[$selectedId])) {
                return $this->redirectWithError(
                    $this->text('mod_contextgroups_err_bulkpage')
                );
            }
        }

        $groupIds = $this->contextRoleGroupIds();
        $knownUsers = array();
        foreach ($this->allSystemUsers($groupIds['student']) as $user) {
            $knownUsers[$this->userIdFromRecord($user)] = true;
        }
        foreach ($listedIds as $listedId) {
            if (!isset($knownUsers[$listedId])) {
                return $this->redirectWithError(
                    $this->text('mod_contextgroups_err_accountmissing')
                );
            }
        }

        $desired = $this->currentRoleState($listedIds, $groupIds);
        $selectedMap = array_fill_keys($selectedIds, true);
        foreach ($listedIds as $listedId) {
            $makeStudent = isset($selectedMap[$listedId]);
            if ($makeStudent
                && $listedId === (string) $this->userId
                && $desired[$listedId]['lecturer']) {
                return $this->redirectWithError(
                    $this->text('mod_contextgroups_err_selfstudent')
                );
            }
            $desired[$listedId]['student'] = $makeStudent;
            if ($makeStudent) {
                $desired[$listedId]['lecturer'] = false;
                $desired[$listedId]['guest'] = false;
            }
        }

        $error = $this->reconcileRoleStates($desired, $groupIds);
        if ($error !== null) {
            return $this->redirectWithError($error);
        }

        return $this->nextAction(null, $this->bulkReturnParams(
            $this->text('mod_contextgroups_msg_bulkupdated')
        ));
    }

    private function removeAllStudents()
    {
        $validationError = $this->mutationValidationError();
        if ($validationError !== null) {
            return $this->redirectWithError($validationError);
        }
        if ((string) $this->getParam('confirmremoveall', '') !== 'yes') {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_removeallconfirm')
            );
        }

        $groupIds = $this->contextRoleGroupIds();
        $studentIds = array();
        foreach ($this->objGroupService->getMembers($groupIds['student']) as $student) {
            $studentId = $this->userIdFromRecord($student);
            if ($studentId !== '') {
                $studentIds[] = $studentId;
            }
        }
        if ($studentIds === array()) {
            return $this->nextAction(null, array(
                'message' => $this->text('mod_contextgroups_msg_nostudents'),
            ));
        }

        $desired = $this->currentRoleState($studentIds, $groupIds);
        foreach ($studentIds as $studentId) {
            $desired[$studentId]['student'] = false;
        }
        $error = $this->reconcileRoleStates($desired, $groupIds);
        if ($error !== null) {
            return $this->redirectWithError($error);
        }

        return $this->nextAction(null, array(
            'message' => $this->text(
                'mod_contextgroups_msg_studentsremoved',
                array('COUNT' => count($studentIds))
            ),
        ));
    }

    private function importStudents()
    {
        $validationError = $this->mutationValidationError();
        if ($validationError !== null) {
            return $this->redirectWithError($validationError);
        }

        if (!isset($_FILES['studentfile'])
            || !is_array($_FILES['studentfile'])) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_choosecsv')
            );
        }
        $upload = $_FILES['studentfile'];
        $uploadError = isset($upload['error']) ? (int) $upload['error'] : -1;
        if ($uploadError !== UPLOAD_ERR_OK) {
            return $this->redirectWithError(
                $this->text(
                    'mod_contextgroups_err_csvupload',
                    array('ERROR' => $uploadError)
                )
            );
        }
        $uploadSize = isset($upload['size']) ? (int) $upload['size'] : 0;
        $uploadName = isset($upload['name']) ? (string) $upload['name'] : '';
        $uploadPath = isset($upload['tmp_name']) ? (string) $upload['tmp_name'] : '';
        if ($uploadSize < 1 || $uploadSize > self::CSV_MAX_BYTES
            || strtolower(pathinfo($uploadName, PATHINFO_EXTENSION)) !== 'csv'
            || !is_uploaded_file($uploadPath)) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_csvinvalid')
            );
        }

        $groupIds = $this->contextRoleGroupIds();
        $users = $this->allSystemUsers($groupIds['student']);
        $indexes = $this->userIndexes($users);
        $parseResult = $this->parseStudentCsv($uploadPath, $indexes);
        if ($parseResult['error'] !== null) {
            return $this->redirectWithError($parseResult['error']);
        }
        $studentIds = $parseResult['userIds'];
        if ($studentIds === array()) {
            return $this->redirectWithError(
                $this->text('mod_contextgroups_err_csvnousers')
            );
        }

        $desired = $this->currentRoleState($studentIds, $groupIds);
        $alreadyStudents = 0;
        foreach ($studentIds as $studentId) {
            if ($desired[$studentId]['student']) {
                $alreadyStudents++;
            }
            if ($studentId === (string) $this->userId
                && $desired[$studentId]['lecturer']) {
                return $this->redirectWithError(
                    $this->text('mod_contextgroups_err_csvselflecturer')
                );
            }
            $desired[$studentId]['student'] = true;
            $desired[$studentId]['lecturer'] = false;
            $desired[$studentId]['guest'] = false;
        }

        $error = $this->reconcileRoleStates($desired, $groupIds);
        if ($error !== null) {
            return $this->redirectWithError($error);
        }

        return $this->nextAction(null, array(
            'message' => $this->text(
                'mod_contextgroups_msg_csvresult',
                array(
                    'MATCHED' => count($studentIds),
                    'ADDED' => count($studentIds) - $alreadyStudents,
                    'EXISTING' => $alreadyStudents,
                )
            ),
        ));
    }

    private function parseStudentCsv($path, array $indexes)
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return array(
                'userIds' => array(),
                'error' => $this->text('mod_contextgroups_err_csvunreadable'),
            );
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return array(
                'userIds' => array(),
                'error' => $this->text('mod_contextgroups_err_csvempty'),
            );
        }
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }
        $columns = array();
        foreach ($header as $index => $name) {
            $normalised = strtolower(trim((string) $name));
            if ($normalised === 'user_id') {
                $normalised = 'userid';
            }
            if ($normalised === 'emailaddress') {
                $normalised = 'email';
            }
            if (in_array($normalised, array('userid', 'username', 'email'), true)) {
                $columns[$normalised] = $index;
            }
        }
        if ($columns === array()) {
            fclose($handle);
            return array(
                'userIds' => array(),
                'error' => $this->text('mod_contextgroups_err_csvheader'),
            );
        }

        $resolved = array();
        $errors = array();
        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if ($lineNumber > self::CSV_MAX_ROWS + 1) {
                $errors[] = $this->text(
                    'mod_contextgroups_err_csvrows',
                    array('MAX' => self::CSV_MAX_ROWS)
                );
                break;
            }
            $values = array();
            foreach ($columns as $column => $index) {
                $value = isset($row[$index]) ? trim((string) $row[$index]) : '';
                if ($value !== '') {
                    $values[$column] = $value;
                }
            }
            if ($values === array()) {
                continue;
            }

            $rowMatches = array();
            foreach ($values as $column => $value) {
                $lookup = $column === 'userid' ? $value : strtolower($value);
                if (!isset($indexes[$column][$lookup])) {
                    $errors[] = $this->text(
                        'mod_contextgroups_err_csvnomatch',
                        array(
                            'LINE' => $lineNumber,
                            'COLUMN' => $column,
                            'VALUE' => $value,
                        )
                    );
                    continue 2;
                }
                if (count($indexes[$column][$lookup]) !== 1) {
                    $errors[] = $this->text(
                        'mod_contextgroups_err_csvambiguous',
                        array(
                            'LINE' => $lineNumber,
                            'COLUMN' => $column,
                            'VALUE' => $value,
                        )
                    );
                    continue 2;
                }
                $rowMatches[] = $indexes[$column][$lookup][0];
            }
            if (count(array_unique($rowMatches)) !== 1) {
                $errors[] = $this->text(
                    'mod_contextgroups_err_csvdifferentusers',
                    array('LINE' => $lineNumber)
                );
                continue;
            }
            $resolved[$rowMatches[0]] = true;
        }
        fclose($handle);

        if ($errors !== array()) {
            $visibleErrors = array_slice($errors, 0, 8);
            if (count($errors) > count($visibleErrors)) {
                $visibleErrors[] = $this->text(
                    'mod_contextgroups_err_csvmore',
                    array(
                        'COUNT' => count($errors) - count($visibleErrors),
                    )
                );
            }
            return array(
                'userIds' => array(),
                'error' => implode(' ', $visibleErrors) . ' '
                    . $this->text('mod_contextgroups_err_nochanges'),
            );
        }

        return array('userIds' => array_keys($resolved), 'error' => null);
    }

    private function userIndexes(array $users)
    {
        $indexes = array('userid' => array(), 'username' => array(), 'email' => array());
        foreach ($users as $user) {
            $values = array(
                'userid' => $this->userIdFromRecord($user),
                'username' => isset($user['username'])
                    ? strtolower(trim((string) $user['username']))
                    : '',
                'email' => isset($user['email'])
                    ? strtolower(trim((string) $user['email']))
                    : '',
            );
            foreach ($values as $column => $value) {
                if ($value === '') {
                    continue;
                }
                if (!isset($indexes[$column][$value])) {
                    $indexes[$column][$value] = array();
                }
                $indexes[$column][$value][] = $values['userid'];
            }
        }

        return $indexes;
    }

    private function currentRoleState(array $userIds, array $groupIds)
    {
        $state = array();
        foreach ($userIds as $userId) {
            $state[$userId] = array_fill_keys(array_keys($groupIds), false);
        }
        foreach ($groupIds as $role => $groupId) {
            foreach ($this->objGroupService->getMembers($groupId) as $member) {
                $memberId = $this->userIdFromRecord($member);
                if (isset($state[$memberId])) {
                    $state[$memberId][$role] = true;
                }
            }
        }

        return $state;
    }

    private function reconcileRoleStates(array $desired, array $groupIds)
    {
        $userIds = array_keys($desired);
        $before = $this->currentRoleState($userIds, $groupIds);
        $permissionIds = array();
        foreach ($userIds as $userId) {
            $permissionId = $this->objIdentityService
                ->permissionUserIdForUser($userId);
            if ($permissionId === null) {
                return $this->text('mod_contextgroups_err_noidentity');
            }
            $permissionIds[$userId] = $permissionId;
        }

        foreach ($desired as $userId => $roleState) {
            foreach ($roleState as $role => $shouldBelong) {
                if ($before[$userId][$role] === (bool) $shouldBelong) {
                    continue;
                }
                $success = $shouldBelong
                    ? $this->objGroupService->ensureMembership(
                        $groupIds[$role],
                        $permissionIds[$userId]
                    )
                    : $this->objGroupService->removeMembership(
                        $groupIds[$role],
                        $permissionIds[$userId]
                    );
                if (!$success) {
                    $rollbackOk = $this->restoreRoleStates(
                        $before,
                        $groupIds,
                        $permissionIds
                    );
                    return $rollbackOk
                        ? $this->text('mod_contextgroups_err_updaterolledback')
                        : $this->text('mod_contextgroups_err_rollbackfailed');
                }
            }
        }

        return null;
    }

    private function restoreRoleStates(
        array $state,
        array $groupIds,
        array $permissionIds
    ) {
        $ok = true;
        foreach ($state as $userId => $roleState) {
            foreach ($roleState as $role => $shouldBelong) {
                $result = $shouldBelong
                    ? $this->objGroupService->ensureMembership(
                        $groupIds[$role],
                        $permissionIds[$userId]
                    )
                    : $this->objGroupService->removeMembership(
                        $groupIds[$role],
                        $permissionIds[$userId]
                    );
                $ok = $result && $ok;
            }
        }

        return $ok;
    }

    private function mutationValidationError()
    {
        $method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper((string) $_SERVER['REQUEST_METHOD'])
            : '';
        if ($method !== 'POST') {
            return $this->text('mod_contextgroups_err_postrequired');
        }

        $submittedContext = (string) $this->getParam('context', '');
        if ($submittedContext !== $this->contextStore->getContextCode()) {
            return $this->text('mod_contextgroups_err_contextchanged');
        }

        $submittedToken = (string) $this->getParam('membershiptoken', '');
        $storedToken = $this->membershipToken(false);
        if ($storedToken === '' || $submittedToken === ''
            || !hash_equals($storedToken, $submittedToken)) {
            return $this->text('mod_contextgroups_err_formexpired');
        }

        return null;
    }

    private function membershipToken($create)
    {
        $token = (string) $this->getSession('membershipToken', '');
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $token;
        }
        if (!$create) {
            return '';
        }

        $token = bin2hex(random_bytes(32));
        $this->setSession('membershipToken', $token);

        return $token;
    }

    private function contextRoleGroupIds()
    {
        $contextCode = $this->contextStore->getContextCode();
        $groupIds = array();
        foreach ($this->roleDefinitions() as $role => $definition) {
            $groupId = $this->objGroupService->groupIdForName(
                $contextCode . '^' . $definition['storedName']
            );
            if ($groupId === false) {
                throw new RuntimeException($this->text(
                    'mod_contextgroups_err_rolegroup',
                    array('ROLE' => $definition['storedName'])
                ));
            }
            $groupIds[$role] = $groupId;
        }

        return $groupIds;
    }

    private function roleDefinitions()
    {
        return array(
            'lecturer' => array(
                'storedName' => 'Lecturers',
                'singular' => $this->text('mod_contextgroups_rolelecturer'),
                'plural' => $this->text('mod_contextgroups_rolelecturers'),
            ),
            'student' => array(
                'storedName' => 'Students',
                'singular' => $this->text('mod_contextgroups_rolestudent'),
                'plural' => $this->text('mod_contextgroups_rolestudents'),
            ),
            'guest' => array(
                'storedName' => 'Guest',
                'singular' => $this->text('mod_contextgroups_roleguest'),
                'plural' => $this->text('mod_contextgroups_roleguests'),
            ),
        );
    }

    private function text($code, array $replacements = array())
    {
        $text = $this->languageService->code2Txt(
            (string) $code,
            'contextgroups'
        );
        if ($replacements === array()) {
            return $text;
        }

        $tokens = array();
        foreach ($replacements as $tag => $value) {
            $tokens['[-' . strtoupper((string) $tag) . '-]'] = (string) $value;
        }

        return strtr($text, $tokens);
    }

    private function pageTexts()
    {
        $keys = array(
            'unnameduser', 'membershipeyebrow', 'managetitle', 'contextcode',
            'intro', 'individualmembership', 'findmember', 'searchlabel',
            'search', 'searchresults', 'searchlimited', 'nomatches',
            'currentrole', 'notincontext', 'chooserole', 'alreadyrole',
            'addas', 'largecontexts',
            'bulkheading', 'bulkintro', 'filteraccounts', 'filter', 'clear',
            'nofiltermatches', 'account', 'currentcontextrole',
            'studentmembership', 'youraccount', 'showing', 'accounttabletoggle',
            'selectdisplayed', 'cleardisplayed', 'savedisplayed',
            'accountpages', 'previous', 'next', 'pageof', 'uploadheading',
            'uploadhelp', 'csvfile', 'uploadadd', 'removeall',
            'removeallhelp', 'removeallconfirm', 'currentmembership',
            'currentmembers', 'membercount', 'norole', 'you', 'remove',
            'showingfirst', 'usebulk',
        );
        $texts = array();
        foreach ($keys as $key) {
            $texts[$key] = $this->text('mod_contextgroups_ui_' . $key);
        }

        return $texts;
    }

    private function normaliseRole($value)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = strtolower(trim((string) $value));

        return array_key_exists($value, $this->roleDefinitions())
            ? $value
            : null;
    }

    private function normaliseUserId($value)
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 255
            || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }

        return $value;
    }

    private function normaliseUserIdList($value)
    {
        if ($value === null || $value === '') {
            return array();
        }
        if (!is_array($value)) {
            return null;
        }
        $result = array();
        foreach ($value as $item) {
            $userId = $this->normaliseUserId($item);
            if ($userId === null) {
                return null;
            }
            $result[$userId] = true;
        }

        return array_keys($result);
    }

    private function positiveInteger($value, $fallback)
    {
        if (!is_scalar($value) || !preg_match('/^[0-9]+$/', (string) $value)) {
            return $fallback;
        }
        $integer = (int) $value;

        return $integer > 0 ? $integer : $fallback;
    }

    private function userIdFromRecord(array $record)
    {
        if (!array_key_exists('userId', $record)
            || !is_scalar($record['userId'])) {
            return '';
        }

        return trim((string) $record['userId']);
    }

    private function bulkReturnParams($message)
    {
        return array(
            'message' => (string) $message,
            'bulksearch' => trim((string) $this->getParam('bulksearch', '')),
            'bulkpage' => $this->positiveInteger(
                $this->getParam('bulkpage', 1),
                1
            ),
        );
    }

    private function redirectWithError($message)
    {
        return $this->nextAction(null, array('error' => (string) $message));
    }
}
?>
