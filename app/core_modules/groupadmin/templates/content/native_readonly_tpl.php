<?php
/** Native read-only group administration — Milestone 15 Pass 2. */

$snapshot = isset($groupAdminSnapshot) && is_array($groupAdminSnapshot)
    ? $groupAdminSnapshot
    : array();

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Chisimba's URI helper already returns HTML entities. Decode once before
// escaping so ampersands are not emitted as literal "&amp;" query parameters.
$escapeUrl = function ($value) {
    return htmlspecialchars(
        html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
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
$showContexts = !empty($snapshot['showContexts']);
$csrfToken = isset($groupAdminCsrfToken) ? (string) $groupAdminCsrfToken : '';
$messageCode = isset($groupAdminMessage) ? (string) $groupAdminMessage : '';
$errorCode = isset($groupAdminError) ? (string) $groupAdminError : '';

$baseParams = array('action' => 'native');
$baseParams['showcontexts'] = $showContexts ? '1' : '0';
if ($selectedId !== null) {
    $baseParams['groupid'] = $selectedId;
}

$buildUrl = function (array $changes) use ($baseParams) {
    return $this->uri(array_merge($baseParams, $changes), 'groupadmin');
};

$lang = function ($code, $fallback) {
    return $this->objLanguage->languageText($code, 'groupadmin', $fallback);
};

$groupTypeLabel = function ($type) use ($lang) {
    switch ($type) {
        case 'context':
            return $lang('mod_groupadmin_type_context', 'Context');
        case 'subgroup':
            return $lang('mod_groupadmin_type_contextgroup', 'Group');
        default:
            return $lang('mod_groupadmin_type_sitegroup', 'Site');
    }
};

$statusLabel = function ($status) use ($lang) {
    switch (strtolower((string) $status)) {
        case 'active':
        case '1':
            return $lang('mod_groupadmin_status_active', 'Active');
        case 'inactive':
        case '0':
            return $lang('mod_groupadmin_status_inactive', 'Inactive');
        default:
            return $lang('mod_groupadmin_status_unknown', 'Unknown');
    }
};

$headingName = $lang('mod_groupadmin_col_name', 'Name');
$headingUsername = $lang('mod_groupadmin_col_username', 'Username');
$headingEmail = $lang('mod_groupadmin_col_email', 'Email');
$headingStatus = $lang('mod_groupadmin_col_status', 'Status');
$contextGroupsLabel = $this->objLanguage->code2Txt(
    'mod_groupadmin_contextgroups',
    'groupadmin',
    null,
    '[-context-] groups'
);
?>

<link rel="stylesheet" type="text/css" href="<?php echo $escapeUrl($this->getResourceUri('css/native-admin.css', 'groupadmin')); ?>" />

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
                    'mod_groupadmin_native_management',
                    'groupadmin',
                    'Manage direct group membership using the native Chisimba interface.'
                )); ?>
            </p>
        </div>
        <span class="groupadmin-native__badge">
            <?php echo $escape($this->objLanguage->languageText('mod_groupadmin_native', 'groupadmin', 'Native')); ?>
        </span>
    </header>

    <?php
    $noticeMessages = array(
        'member_added' => $lang('mod_groupadmin_member_added', 'The user was added to the group.'),
        'member_removed' => $lang('mod_groupadmin_member_removed', 'The user was removed from the group.'),
    );
    $errorMessages = array(
        'group_not_found' => $lang('mod_groupadmin_group_not_found', 'The selected group could not be found.'),
        'invalid_user' => $lang('mod_groupadmin_invalid_user', 'The selected user is invalid.'),
        'user_not_available' => $lang('mod_groupadmin_user_not_available', 'That user is not available to add to this group.'),
        'permission_user_not_found' => $lang('mod_groupadmin_permission_user_not_found', 'The user has no permission identity.'),
        'already_member' => $lang('mod_groupadmin_already_member', 'That user is already a member of the group.'),
        'not_a_member' => $lang('mod_groupadmin_not_a_member', 'That user is not a direct member of the group.'),
        'cannot_remove_self_admin' => $lang('mod_groupadmin_cannot_remove_self_admin', 'You cannot remove your own Site Admin membership.'),
        'cannot_remove_last_admin' => $lang('mod_groupadmin_cannot_remove_last_admin', 'The final Site Admin member cannot be removed.'),
        'add_failed' => $lang('mod_groupadmin_add_failed', 'The user could not be added to the group.'),
        'remove_failed' => $lang('mod_groupadmin_remove_failed', 'The user could not be removed from the group.'),
        'membership_update_failed' => $lang('mod_groupadmin_membership_update_failed', 'The membership change failed.'),
    );
    ?>
    <?php if ($messageCode !== '' && isset($noticeMessages[$messageCode])): ?>
        <div class="groupadmin-native__notice groupadmin-native__notice--success" role="status">
            <?php echo $escape($noticeMessages[$messageCode]); ?>
        </div>
    <?php endif; ?>
    <?php if ($errorCode !== '' && isset($errorMessages[$errorCode])): ?>
        <div class="groupadmin-native__notice groupadmin-native__notice--error" role="alert">
            <?php echo $escape($errorMessages[$errorCode]); ?>
        </div>
    <?php endif; ?>

    <div class="groupadmin-native__layout">
        <nav class="groupadmin-native__panel groupadmin-native__groups" aria-labelledby="group-list-title">
            <div class="groupadmin-native__panel-heading">
                <h2 id="group-list-title">
                    <?php echo $escape($this->objLanguage->languageText('word_groups', 'groupadmin', 'Groups')); ?>
                </h2>
                <span><?php echo count($groups); ?></span>
            </div>

            <a class="groupadmin-native__context-toggle"
               href="<?php echo $escapeUrl($buildUrl(array(
                   'showcontexts' => $showContexts ? '0' : '1',
                   'groupid' => null,
                   'page' => 1,
               ))); ?>">
                <?php echo $escape(($showContexts
                    ? $lang('mod_groupadmin_hide', 'Hide')
                    : $lang('mod_groupadmin_show', 'Show')) . ' ' . $contextGroupsLabel); ?>
            </a>

            <?php if (!$groups): ?>
                <p role="status"><?php echo $escape($this->objLanguage->languageText('mod_groupadmin_nogroupsfound', 'groupadmin', 'No groups were found.')); ?></p>
            <?php else: ?>
                <ul class="groupadmin-native__group-list">
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $isCurrent = (string) $selectedId === (string) $group['id'];
                        ?>
                        <li class="<?php echo $group['type'] === 'subgroup'
                            ? 'groupadmin-native__group-item--nested'
                            : ($group['type'] === 'context' ? 'groupadmin-native__group-item--context' : ''); ?>">
                            <form class="groupadmin-native__group-form"
                                  method="get"
                                  action="<?php echo $escapeUrl($this->uri(array(), 'groupadmin')); ?>">
                                <input type="hidden" name="module" value="groupadmin" />
                                <input type="hidden" name="action" value="native" />
                                <input type="hidden" name="groupid" value="<?php echo $escape($group['id']); ?>" />
                                <input type="hidden" name="q" value="<?php echo $escape($query); ?>" />
                                <input type="hidden" name="sort" value="<?php echo $escape($sort); ?>" />
                                <input type="hidden" name="dir" value="<?php echo $escape($direction); ?>" />
                                <input type="hidden" name="limit" value="<?php echo (int) $limit; ?>" />
                                <input type="hidden" name="showcontexts" value="<?php echo $showContexts ? '1' : '0'; ?>" />
                                <button type="submit"
                                        class="groupadmin-native__group-button chisimba-selectable"
                                        <?php echo $isCurrent ? 'aria-current="page"' : ''; ?>>
                                    <span class="groupadmin-native__group-name"><?php echo $escape($group['name']); ?></span>
                                    <span class="groupadmin-native__type groupadmin-native__type--<?php echo $escape($group['type']); ?>">
                                        <?php echo $escape($groupTypeLabel($group['type'])); ?>
                                    </span>
                                </button>
                            </form>
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
                        <p class="groupadmin-native__eyebrow"><?php echo $escape($groupTypeLabel($selected['type'])); ?></p>
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

                <form class="groupadmin-native__panel groupadmin-native__toolbar" method="get" action="<?php echo $escapeUrl($this->uri(array(), 'groupadmin')); ?>">
                    <input type="hidden" name="module" value="groupadmin" />
                    <input type="hidden" name="action" value="native" />
                    <input type="hidden" name="groupid" value="<?php echo (int) $selectedId; ?>" />
                    <input type="hidden" name="showcontexts" value="<?php echo $showContexts ? '1' : '0'; ?>" />

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
                        <?php
                        $sortLabel = $sort === 'email'
                            ? $headingEmail
                            : ($sort === 'status' ? $headingStatus : $headingName);
                        $directionLabel = $direction === 'desc'
                            ? $lang('mod_groupadmin_direction_descending', 'descending')
                            : $lang('mod_groupadmin_direction_ascending', 'ascending');
                        echo $escape(sprintf(
                            $lang('mod_groupadmin_sortedby', 'Sorted by %s, %s'),
                            $sortLabel,
                            $directionLabel
                        ));
                        ?>
                    </p>
                    <?php if ($query !== ''): ?>
                        <a class="groupadmin-native__button-secondary" href="<?php echo $escapeUrl($buildUrl(array('q' => '', 'page' => 1))); ?>"><?php echo $escape($this->objLanguage->languageText('word_clear', 'system', 'Clear')); ?></a>
                    <?php endif; ?>
                </form>

                <?php
                $tables = array(
                    array(
                        'id' => 'members',
                        'title' => $lang('mod_groupadmin_currentmembers', 'Current members'),
                        'data' => $members,
                        'empty' => $lang('mod_groupadmin_nomatchingmembers', 'No matching members were found.')
                    ),
                    array(
                        'id' => 'available',
                        'title' => $lang('mod_groupadmin_usersnotingroup', 'Users not currently in this group'),
                        'data' => $available,
                        'empty' => $lang('mod_groupadmin_nomatchingavailable', 'No matching available users were found.')
                    ),
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
                                        <thead><tr><th scope="col"><?php echo $escape($headingName); ?></th><th scope="col"><?php echo $escape($headingUsername); ?></th><th scope="col"><?php echo $escape($headingEmail); ?></th><th scope="col"><?php echo $escape($headingStatus); ?></th><th scope="col"><?php echo $escape($lang('mod_groupadmin_col_action', 'Action')); ?></th></tr></thead>
                                        <tbody>
                                            <?php foreach ($table['data']['records'] as $user): ?>
                                                <tr>
                                                    <td data-label="<?php echo $escape($headingName); ?>"><?php echo $escape($user['displayName'] !== '' ? $user['displayName'] : $lang('mod_groupadmin_unnameduser', 'Unnamed user')); ?></td>
                                                    <td data-label="<?php echo $escape($headingUsername); ?>"><?php echo $escape($user['username']); ?></td>
                                                    <td data-label="<?php echo $escape($headingEmail); ?>"><?php echo $escape($user['email']); ?></td>
                                                    <td data-label="<?php echo $escape($headingStatus); ?>"><span class="groupadmin-native__status"><?php echo $escape($statusLabel($user['status'])); ?></span></td>
                                                    <td data-label="<?php echo $escape($lang('mod_groupadmin_col_action', 'Action')); ?>">
                                                        <form class="groupadmin-native__membership-form" method="post" action="<?php echo $escapeUrl($this->uri(array(), 'groupadmin')); ?>">
                                                            <input type="hidden" name="module" value="groupadmin" />
                                                            <input type="hidden" name="action" value="<?php echo $table['id'] === 'members' ? 'removemember' : 'addmember'; ?>" />
                                                            <input type="hidden" name="groupid" value="<?php echo (int) $selectedId; ?>" />
                                                            <input type="hidden" name="userid" value="<?php echo $escape($user['userId']); ?>" />
                                                            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>" />
                                                            <input type="hidden" name="q" value="<?php echo $escape($query); ?>" />
                                                            <input type="hidden" name="sort" value="<?php echo $escape($sort); ?>" />
                                                            <input type="hidden" name="dir" value="<?php echo $escape($direction); ?>" />
                                                            <input type="hidden" name="limit" value="<?php echo (int) $limit; ?>" />
                                                            <input type="hidden" name="page" value="<?php echo (int) $page; ?>" />
                                                            <input type="hidden" name="showcontexts" value="<?php echo $showContexts ? '1' : '0'; ?>" />
                                                            <button type="submit" class="groupadmin-native__membership-button <?php echo $table['id'] === 'members' ? 'groupadmin-native__membership-button--remove' : 'groupadmin-native__membership-button--add'; ?>">
                                                                <?php echo $escape($table['id'] === 'members' ? $lang('mod_groupadmin_remove_user', 'Remove') : $lang('mod_groupadmin_add_user', 'Add')); ?>
                                                            </button>
                                                        </form>
                                                    </td>
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
                    <nav class="groupadmin-native__pagination" aria-label="<?php echo $escape($lang('mod_groupadmin_userresultpages', 'User result pages')); ?>">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $escapeUrl($buildUrl(array('q' => $query, 'sort' => $sort, 'dir' => $direction, 'limit' => $limit, 'page' => $page - 1))); ?>"><?php echo $escape($lang('mod_groupadmin_previous', 'Previous')); ?></a>
                        <?php endif; ?>
                        <span><?php echo $escape(sprintf($lang('mod_groupadmin_pageof', 'Page %d of %d'), (int) $page, (int) max($members['pages'], $available['pages']))); ?></span>
                        <?php if ($page < max($members['pages'], $available['pages'])): ?>
                            <a href="<?php echo $escapeUrl($buildUrl(array('q' => $query, 'sort' => $sort, 'dir' => $direction, 'limit' => $limit, 'page' => $page + 1))); ?>"><?php echo $escape($lang('mod_groupadmin_next', 'Next')); ?></a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</section>
