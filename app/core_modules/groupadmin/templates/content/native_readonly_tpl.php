<?php
/** Native read-only group administration — Milestone 15 Pass 2. */

$snapshot = isset($groupAdminSnapshot) && is_array($groupAdminSnapshot)
    ? $groupAdminSnapshot
    : array();

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$groups = isset($snapshot['groups']['records']) ? $snapshot['groups']['records'] : array();
$members = isset($snapshot['members']) ? $snapshot['members'] : array('records' => array(), 'total' => 0, 'page' => 1, 'pages' => 1);
$available = isset($snapshot['availableUsers']) ? $snapshot['availableUsers'] : array('records' => array(), 'total' => 0, 'page' => 1, 'pages' => 1);
$selected = isset($snapshot['selectedGroup']) ? $snapshot['selectedGroup'] : null;
$selectedId = isset($snapshot['selectedGroupId']) ? $snapshot['selectedGroupId'] : null;
$query = isset($snapshot['query']) ? $snapshot['query'] : '';
$sort = isset($snapshot['sort']) ? $snapshot['sort'] : 'name';
$direction = isset($snapshot['direction']) ? $snapshot['direction'] : 'asc';
$page = isset($snapshot['page']) ? $snapshot['page'] : 1;
$limit = isset($snapshot['limit']) ? $snapshot['limit'] : 25;

$baseParams = array('action' => 'native');
if ($selectedId !== null) {
    $baseParams['groupid'] = $selectedId;
}

$buildUrl = function (array $changes) use ($baseParams) {
    return $this->uri(array_merge($baseParams, $changes), 'groupadmin');
};
?>

<link rel="stylesheet" type="text/css" href="<?php echo $escape($this->getResourceUri('css/native-admin.css', 'groupadmin')); ?>" />

<section class="groupadmin-native" aria-labelledby="groupadmin-title">
    <header class="groupadmin-native__header">
        <div>
            <p class="groupadmin-native__eyebrow">
                <?php echo $escape($this->objLanguage->languageText('mod_groupadmin_administration', 'groupadmin', 'Administration')); ?>
            </p>
            <h1 id="groupadmin-title">
                <?php echo $escape($this->objLanguage->languageText('mod_groupadmin_name', 'groupadmin', 'Group administration')); ?>
            </h1>
            <p>
                <?php echo $escape($this->objLanguage->languageText(
                    'mod_groupadmin_native_readonly',
                    'groupadmin',
                    'Read-only native interface. Membership changes remain disabled during this migration stage.'
                )); ?>
            </p>
        </div>
        <span class="groupadmin-native__badge">
            <?php echo $escape($this->objLanguage->languageText('mod_groupadmin_readonly', 'groupadmin', 'Read only')); ?>
        </span>
    </header>

    <div class="groupadmin-native__layout">
        <nav class="groupadmin-native__panel groupadmin-native__groups" aria-labelledby="group-list-title">
            <div class="groupadmin-native__panel-heading">
                <h2 id="group-list-title">
                    <?php echo $escape($this->objLanguage->languageText('word_groups', 'groupadmin', 'Groups')); ?>
                </h2>
                <span><?php echo count($groups); ?></span>
            </div>

            <?php if (!$groups): ?>
                <p role="status"><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_nogroupsfound', 'groupadmin', 'No groups were found.')); ?></p>
            <?php else: ?>
                <ul class="groupadmin-native__group-list">
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $isCurrent = (string) $selectedId === (string) $group['id'];
                        $url = $this->uri(array(
                            'action' => 'native',
                            'groupid' => $group['id'],
                            'q' => $query,
                            'sort' => $sort,
                            'dir' => $direction,
                            'limit' => $limit
                        ), 'groupadmin');
                        ?>
                        <li>
                            <a href="<?php echo $escape($url); ?>" <?php echo $isCurrent ? 'aria-current="page"' : ''; ?>>
                                <span class="groupadmin-native__group-name"><?php echo $escape($group['name']); ?></span>
                                <span class="groupadmin-native__type groupadmin-native__type--<?php echo $escape($group['type']); ?>">
                                    <?php echo $escape($group['typeLabel']); ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </nav>

        <main class="groupadmin-native__content">
            <?php if ($selectedId === null): ?>
                <section class="groupadmin-native__panel groupadmin-native__empty" aria-labelledby="select-group-title">
                    <h2 id="select-group-title"><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_selectgroup', 'groupadmin', 'Select a group')); ?></h2>
                    <p><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_selectgrouphelp', 'groupadmin', 'Choose a group to inspect its current and available users.')); ?></p>
                </section>
            <?php elseif ($selected === null): ?>
                <section class="groupadmin-native__panel" role="alert">
                    <h2><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_groupnotfound', 'groupadmin', 'Group not found')); ?></h2>
                    <p><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_groupnotfoundhelp', 'groupadmin', 'The requested group is not present in the current group list.')); ?></p>
                </section>
            <?php else: ?>
                <section class="groupadmin-native__panel groupadmin-native__summary" aria-labelledby="selected-group-title">
                    <div>
                        <p class="groupadmin-native__eyebrow"><?php echo $escape($selected['typeLabel']); ?></p>
                        <h2 id="selected-group-title"><?php echo $escape($selected['name']); ?></h2>
                        <?php if ($selected['contextCode'] !== ''): ?>
                            <p><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_contextcode', 'groupadmin', 'Context code')); ?>: <code><?php echo $escape($selected['contextCode']); ?></code></p>
                        <?php endif; ?>
                    </div>
                    <dl class="groupadmin-native__metrics">
                        <div><dt><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_members', 'groupadmin', 'Members')); ?></dt><dd><?php echo (int) $members['total']; ?></dd></div>
                        <div><dt><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_available', 'groupadmin', 'Available')); ?></dt><dd><?php echo (int) $available['total']; ?></dd></div>
                    </dl>
                </section>

                <form class="groupadmin-native__panel groupadmin-native__toolbar" method="get" action="<?php echo $escape($this->uri(array(), 'groupadmin')); ?>">
                    <input type="hidden" name="module" value="groupadmin" />
                    <input type="hidden" name="action" value="native" />
                    <input type="hidden" name="groupid" value="<?php echo (int) $selectedId; ?>" />

                    <div class="groupadmin-native__field groupadmin-native__field--search">
                        <label for="groupadmin-search"><?php echo $escape($this->objLanguage->languageText('word_search', 'system', 'Search users')); ?></label>
                        <input id="groupadmin-search" name="q" type="search" maxlength="100" value="<?php echo $escape($query); ?>" />
                    </div>
                    <div class="groupadmin-native__field">
                        <label for="groupadmin-sort"><?php echo $escape($this->objLanguage->languageText('word_sortby', 'system', 'Sort by')); ?></label>
                        <select id="groupadmin-sort" name="sort">
                            <option value="name" <?php echo $sort === 'name' ? 'selected="selected"' : ''; ?>><?php echo $escape($this->objLanguage->languageText('word_name', 'system', 'Name')); ?></option>
                            <option value="email" <?php echo $sort === 'email' ? 'selected="selected"' : ''; ?>><?php echo $escape($this->objLanguage->languageText('word_email', 'security', 'Email')); ?></option>
                            <option value="status" <?php echo $sort === 'status' ? 'selected="selected"' : ''; ?>><?php echo $escape($this->objLanguage->languageText('word_status', 'security', 'Status')); ?></option>
                        </select>
                    </div>
                    <div class="groupadmin-native__field">
                        <label for="groupadmin-direction"><?php echo $escape($this->objLanguage->languageText('word_direction', 'system', 'Direction')); ?></label>
                        <select id="groupadmin-direction" name="dir">
                            <option value="asc" <?php echo $direction === 'asc' ? 'selected="selected"' : ''; ?>><?php echo $escape($this->objLanguage->languageText('word_ascending', 'system', 'Ascending')); ?></option>
                            <option value="desc" <?php echo $direction === 'desc' ? 'selected="selected"' : ''; ?>><?php echo $escape($this->objLanguage->languageText('word_descending', 'system', 'Descending')); ?></option>
                        </select>
                    </div>
                    <div class="groupadmin-native__field">
                        <label for="groupadmin-limit"><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_perpage', 'groupadmin', 'Per page')); ?></label>
                        <select id="groupadmin-limit" name="limit">
                            <?php foreach (array(10, 25, 50, 100) as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo (int) $limit === $option ? 'selected="selected"' : ''; ?>><?php echo $option; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit"><?php echo $escape($this->objLanguage->languageText('word_apply', 'system', 'Apply')); ?></button>
                    <p class="groupadmin-native__sort-state" role="status">
                        <?php echo $escape(sprintf(
                            'Sorted by %s, %s',
                            $sort === 'email' ? 'email' : ($sort === 'status' ? 'status' : 'name'),
                            $direction === 'desc' ? 'descending' : 'ascending'
                        )); ?>
                    </p>
                    <?php if ($query !== ''): ?>
                        <a class="groupadmin-native__button-secondary" href="<?php echo $escape($buildUrl(array())); ?>"><?php echo $escape($this->objLanguage->languageText('word_clear', 'system', 'Clear')); ?></a>
                    <?php endif; ?>
                </form>

                <?php
                $tables = array(
                    array('id' => 'members', 'title' => 'Current members', 'data' => $members, 'empty' => 'No matching members were found.'),
                    array('id' => 'available', 'title' => 'Users not currently in this group', 'data' => $available, 'empty' => 'No matching available users were found.'),
                );
                ?>
                <div class="groupadmin-native__tables">
                    <?php foreach ($tables as $table): ?>
                        <section class="groupadmin-native__panel" aria-labelledby="<?php echo $escape($table['id']); ?>-title">
                            <div class="groupadmin-native__panel-heading">
                                <h2 id="<?php echo $escape($table['id']); ?>-title"><?php echo $escape($table['title']); ?></h2>
                                <span><?php echo (int) $table['data']['total']; ?></span>
                            </div>

                            <?php if (!$table['data']['records']): ?>
                                <p role="status"><?php echo $escape($table['empty']); ?></p>
                            <?php else: ?>
                                <div class="groupadmin-native__table-wrap">
                                    <table>
                                        <thead><tr><th scope="col">Name</th><th scope="col">Username</th><th scope="col">Email</th><th scope="col">Status</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($table['data']['records'] as $user): ?>
                                                <tr>
                                                    <td data-label="Name"><?php echo $escape($user['displayName']); ?></td>
                                                    <td data-label="Username"><?php echo $escape($user['username']); ?></td>
                                                    <td data-label="Email"><?php echo $escape($user['email']); ?></td>
                                                    <td data-label="Status"><span class="groupadmin-native__status"><?php echo $escape($user['status']); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>

                <?php if ($members['pages'] > 1 || $available['pages'] > 1): ?>
                    <nav class="groupadmin-native__pagination" aria-label="User result pages">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $escape($buildUrl(array('q' => $query, 'sort' => $sort, 'dir' => $direction, 'limit' => $limit, 'page' => $page - 1))); ?>">Previous</a>
                        <?php endif; ?>
                        <span>Page <?php echo (int) $page; ?> of <?php echo (int) max($members['pages'], $available['pages']); ?></span>
                        <?php if ($page < max($members['pages'], $available['pages'])): ?>
                            <a href="<?php echo $escape($buildUrl(array('q' => $query, 'sort' => $sort, 'dir' => $direction, 'limit' => $limit, 'page' => $page + 1))); ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</section>
