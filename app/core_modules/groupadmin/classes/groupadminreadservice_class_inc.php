<?php
/**
 * Read-only presentation adapter for native group administration.
 *
 * Domain data comes exclusively from groupservice. This class applies
 * authorization, filtering, sorting and pagination for the native interface.
 * It deliberately exposes no mutation methods.
 *
 * @package groupadmin
 */

// Security check consistent with the Chisimba runtime entry point.
if (empty($GLOBALS['kewl_entry_point_run'])) {
    die('You cannot view this page directly');
}

class groupadminreadservice extends ChisimbaObject
{
    private $objGroupService;
    private $objAuthorization;

    public function init()
    {
        $this->objGroupService = $this->getObject('groupservice', 'groupadmin');
        $this->objAuthorization = $this->getObject('groupadminauthorizationservice', 'groupadmin');
    }

    private function assertAdministrator()
    {
        if (!$this->objAuthorization->isCurrentUserSiteAdministrator()) {
            throw new Exception('Administrator authorization required.');
        }
    }

    /**
     * Return a normalized, filtered, sorted and paginated read-only snapshot.
     */
    public function getSnapshot(
        $groupId = null,
        $page = 1,
        $limit = 25,
        $query = '',
        $sort = 'name',
        $direction = 'asc',
        $showContexts = false
    ) {
        $this->assertAdministrator();

        $groupId = $this->normaliseOptionalPositiveInteger($groupId);
        $page = $this->normalisePositiveInteger($page, 1);
        $limit = $this->normaliseLimit($limit, 25);
        $query = $this->normaliseQuery($query);
        $sort = $this->normaliseSort($sort);
        $direction = $this->normaliseDirection($direction);
        $showContexts = $this->normaliseBoolean($showContexts);

        // Load the hierarchy through the canonical domain service.
        $groups = $this->loadGroupHierarchy();
        $groups = $this->orderGroupHierarchy($groups, $showContexts);

        $selectedGroup = $this->findGroup($groups, $groupId);
        $members = array();
        $availableUsers = array();

        if ($groupId !== null) {
            $members = $this->objGroupService->getMembers($groupId);
            $availableUsers = $this->objGroupService->getAvailableUsers($groupId);
        }

        $members = $this->filterUsers($members, $query);
        $availableUsers = $this->filterUsers($availableUsers, $query);
        $members = $this->sortRecords($members, $sort, $direction);
        $availableUsers = $this->sortRecords($availableUsers, $sort, $direction);

        return array(
            'success' => true,
            'errors' => array(),
            'selectedGroupId' => $groupId,
            'selectedGroup' => $selectedGroup,
            'groups' => array(
                'records' => $groups,
                'total' => count($groups),
            ),
            'members' => $this->paginate($members, $page, $limit),
            'availableUsers' => $this->paginate($availableUsers, $page, $limit),
            'query' => $query,
            'sort' => $sort,
            'direction' => $direction,
            'page' => $page,
            'limit' => $limit,
            'showContexts' => $showContexts,
        );
    }

    /**
     * Load the canonical ExtJS-neutral hierarchy from groupservice.
     */
    private function loadGroupHierarchy()
    {
        return $this->objGroupService->listGroups();
    }

    /**
     * Keep site groups first. Context containers and their role groups are an
     * optional, nested second section rather than a misleading flat list.
     */
    private function orderGroupHierarchy(array $groups, $showContexts)
    {
        $siteGroups = array();
        $contexts = array();
        $children = array();

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            if (($group['type'] ?? '') === 'context') {
                $contexts[] = $group;
            } elseif (($group['type'] ?? '') === 'subgroup') {
                $parentId = isset($group['parentId']) ? (int) $group['parentId'] : 0;
                $children[$parentId][] = $group;
            } else {
                $siteGroups[] = $group;
            }
        }

        $siteGroups = $this->sortRecords($siteGroups, 'name', 'asc');
        if (!$showContexts) {
            return $siteGroups;
        }

        $contexts = $this->sortRecords($contexts, 'name', 'asc');
        $ordered = $siteGroups;
        foreach ($contexts as $context) {
            $ordered[] = $context;
            $contextChildren = $children[(int) $context['id']] ?? array();
            $contextChildren = $this->sortRecords($contextChildren, 'name', 'asc');
            foreach ($contextChildren as $child) {
                $ordered[] = $child;
            }
        }
        return $ordered;
    }





    private function normaliseStatus($value)
    {
        if ($value === true || $value === 1 || $value === '1') {
            return 'Active';
        }
        if ($value === false || $value === 0 || $value === '0') {
            return 'Inactive';
        }
        $value = trim((string) $value);
        return $value !== '' ? $value : 'Unknown';
    }

    private function filterUsers(array $records, $query)
    {
        if ($query === '') {
            return $records;
        }
        $needle = $this->lower($query);
        return array_values(array_filter($records, function ($record) use ($needle) {
            $haystack = $this->lower(
                $record['displayName'] . ' ' . $record['username'] . ' ' . $record['email']
            );
            return strpos($haystack, $needle) !== false;
        }));
    }

    private function sortRecords(array $records, $sort, $direction)
    {
        $isGroupList = isset($records[0]['name']);
        $primaryKey = $isGroupList
            ? 'name'
            : ($sort === 'email' ? 'email' : ($sort === 'status' ? 'status' : 'displayName'));

        usort($records, function ($left, $right) use ($primaryKey, $direction, $isGroupList) {
            $keys = $isGroupList
                ? array($primaryKey, 'id')
                : array($primaryKey, 'displayName', 'username', 'email', 'id');

            $result = 0;
            foreach ($keys as $key) {
                $a = isset($left[$key]) ? $left[$key] : '';
                $b = isset($right[$key]) ? $right[$key] : '';

                if (is_numeric($a) && is_numeric($b)) {
                    $result = (int) $a <=> (int) $b;
                } else {
                    $result = strnatcasecmp($this->lower($a), $this->lower($b));
                }

                if ($result !== 0) {
                    break;
                }
            }

            return $direction === 'desc' ? -$result : $result;
        });

        return $records;
    }

    private function paginate(array $records, $page, $limit)
    {
        $total = count($records);
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);
        $offset = ($page - 1) * $limit;
        return array(
            'records' => array_slice($records, $offset, $limit),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
        );
    }

    private function findGroup(array $groups, $groupId)
    {
        if ($groupId === null) {
            return null;
        }
        foreach ($groups as $group) {
            if ((int) $group['id'] === (int) $groupId) {
                return $group;
            }
        }
        return null;
    }


    private function normaliseOptionalPositiveInteger($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_scalar($value) || !preg_match('/^[1-9]\d*$/', (string) $value)) {
            return null;
        }
        return (int) $value;
    }

    private function normalisePositiveInteger($value, $default)
    {
        if (!is_scalar($value) || !preg_match('/^[1-9]\d*$/', (string) $value)) {
            return $default;
        }
        return (int) $value;
    }

    private function normaliseLimit($value, $default)
    {
        $limit = $this->normalisePositiveInteger($value, $default);
        return min($limit, 100);
    }

    private function normaliseQuery($value)
    {
        if (!is_scalar($value)) {
            return '';
        }
        $value = trim((string) $value);
        return substr($value, 0, 100);
    }

    private function normaliseSort($value)
    {
        return in_array($value, array('name', 'email', 'status'), true) ? $value : 'name';
    }

    private function normaliseDirection($value)
    {
        return strtolower((string) $value) === 'desc' ? 'desc' : 'asc';
    }

    private function normaliseBoolean($value)
    {
        return in_array(strtolower(trim((string) $value)),
            array('1', 'true', 'yes', 'on'), true);
    }

    private function lower($value)
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower((string) $value, 'UTF-8')
            : strtolower((string) $value);
    }

}
?>
