<?php
/**
 * One-page course membership manager with scalable student operations.
 *
 * @package contextgroups
 */

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$message = trim((string) $this->getParam('message', ''));
$error = trim((string) $this->getParam('error', ''));
$contextName = trim((string) $contextTitle) !== ''
    ? (string) $contextTitle
    : (string) $contextCode;
$formatText = function ($text, array $replacements = array()) {
    $tokens = array();
    foreach ($replacements as $tag => $value) {
        $tokens['[-' . strtoupper((string) $tag) . '-]'] = (string) $value;
    }

    return strtr((string) $text, $tokens);
};
$displayName = function (array $person) use ($pageTexts) {
    if (!empty($person['displayName'])) {
        return (string) $person['displayName'];
    }
    $name = trim(
        (isset($person['firstName']) ? (string) $person['firstName'] : '')
        . ' '
        . (isset($person['surname']) ? (string) $person['surname'] : '')
    );
    if ($name !== '') {
        return $name;
    }
    if (!empty($person['username'])) {
        return (string) $person['username'];
    }

    return $pageTexts['unnameduser'];
};
$pageUrl = function ($page) use ($bulkSearch) {
    return 'index.php?' . http_build_query(array(
        'module' => 'contextgroups',
        'bulksearch' => (string) $bulkSearch,
        'bulkpage' => (int) $page,
    )) . '#contextgroups-bulk-students';
};
$bulkFirst = $bulkTotal === 0 ? 0 : $bulkOffset + 1;
$bulkLast = min($bulkOffset + count($bulkUsers), $bulkTotal);
?>

<section class="contextgroups-manager" aria-labelledby="contextgroups-page-title">
    <header class="contextgroups-header">
        <p class="contextgroups-eyebrow"><?php echo $escape($pageTexts['membershipeyebrow']); ?></p>
        <h1 id="contextgroups-page-title"><?php echo $escape($formatText($pageTexts['managetitle'], array('NAME' => $contextName))); ?></h1>
        <p class="contextgroups-course-code">
            <?php echo $escape($pageTexts['contextcode']); ?>: <code><?php echo $escape($contextCode); ?></code>
        </p>
        <p class="contextgroups-intro">
            <?php echo $escape($pageTexts['intro']); ?>
        </p>
    </header>

    <?php if ($message !== ''): ?>
        <div class="contextgroups-notice contextgroups-notice-success" role="status">
            <?php echo $escape($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="contextgroups-notice contextgroups-notice-error" role="alert">
            <?php echo $escape($error); ?>
        </div>
    <?php endif; ?>

    <section class="contextgroups-search" aria-labelledby="contextgroups-search-heading">
        <div class="contextgroups-section-heading">
            <div>
                <p class="contextgroups-eyebrow"><?php echo $escape($pageTexts['individualmembership']); ?></p>
                <h2 id="contextgroups-search-heading"><?php echo $escape($pageTexts['findmember']); ?></h2>
            </div>
        </div>

        <form method="get" action="index.php" class="contextgroups-search-form">
            <input type="hidden" name="module" value="contextgroups" />
            <input type="hidden" name="action" value="searchforusers" />
            <label for="contextgroups-user-search"><?php echo $escape($pageTexts['searchlabel']); ?></label>
            <div class="contextgroups-search-controls">
                <input
                    type="search"
                    id="contextgroups-user-search"
                    name="search"
                    value="<?php echo $escape($search); ?>"
                    maxlength="120"
                    required
                />
                <button type="submit" class="contextgroups-button contextgroups-button-primary">
                    <?php echo $escape($pageTexts['search']); ?>
                </button>
            </div>
        </form>

        <?php if ($search !== ''): ?>
            <div class="contextgroups-search-results" aria-live="polite">
                <div class="contextgroups-section-heading">
                    <h3><?php echo $escape($pageTexts['searchresults']); ?></h3>
                    <span class="contextgroups-count"><?php echo count($searchResults); ?></span>
                </div>
                <?php if ($searchLimited): ?>
                    <p class="contextgroups-search-hint">
                        <?php echo $escape($pageTexts['searchlimited']); ?>
                    </p>
                <?php endif; ?>
                <?php if ($searchResults === array()): ?>
                    <p class="contextgroups-empty"><?php echo $escape($pageTexts['nomatches']); ?></p>
                <?php else: ?>
                    <ul class="contextgroups-result-list">
                        <?php foreach ($searchResults as $result): ?>
                            <?php
                            $resultUserId = isset($result['userId'])
                                ? (string) $result['userId']
                                : '';
                            $resultRoles = isset($result['courseRoles'])
                                && is_array($result['courseRoles'])
                                ? $result['courseRoles']
                                : array();
                            ?>
                            <li class="contextgroups-result">
                                <div class="contextgroups-person">
                                    <strong><?php echo $escape($displayName($result)); ?></strong>
                                    <?php if (!empty($result['username'])): ?>
                                        <span>@<?php echo $escape($result['username']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($result['email'])): ?>
                                        <span><?php echo $escape($result['email']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($resultRoles !== array()): ?>
                                        <span class="contextgroups-current-role">
                                            <?php echo $escape($pageTexts['currentrole']); ?>:
                                            <?php
                                            $resultRoleLabels = array();
                                            foreach ($resultRoles as $resultRole) {
                                                if (isset($roles[$resultRole])) {
                                                    $resultRoleLabels[] = $roles[$resultRole]['singular'];
                                                }
                                            }
                                            echo $escape(implode(', ', $resultRoleLabels));
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="contextgroups-current-role"><?php echo $escape($pageTexts['notincontext']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="contextgroups-role-actions" aria-label="<?php echo $escape($pageTexts['chooserole']); ?>">
                                    <?php foreach ($roles as $role => $definition): ?>
                                        <?php $isCurrentRole = in_array($role, $resultRoles, true); ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="module" value="contextgroups" />
                                            <input type="hidden" name="action" value="addusers" />
                                            <input type="hidden" name="context" value="<?php echo $escape($contextCode); ?>" />
                                            <input type="hidden" name="membershiptoken" value="<?php echo $escape($membershipToken); ?>" />
                                            <input type="hidden" name="userid" value="<?php echo $escape($resultUserId); ?>" />
                                            <input type="hidden" name="role" value="<?php echo $escape($role); ?>" />
                                            <button
                                                type="submit"
                                                class="contextgroups-button<?php echo $isCurrentRole ? ' contextgroups-button-current' : ''; ?>"
                                                <?php echo $isCurrentRole ? 'disabled' : ''; ?>
                                            >
                                                <?php
                                                echo $escape($formatText(
                                                    $isCurrentRole
                                                        ? $pageTexts['alreadyrole']
                                                        : $pageTexts['addas'],
                                                    array('ROLE' => $definition['singular'])
                                                ));
                                                ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section
        id="contextgroups-bulk-students"
        class="contextgroups-bulk"
        aria-labelledby="contextgroups-bulk-heading"
    >
        <div class="contextgroups-section-heading">
            <div>
                <p class="contextgroups-eyebrow"><?php echo $escape($pageTexts['largecontexts']); ?></p>
                <h2 id="contextgroups-bulk-heading"><?php echo $escape($pageTexts['bulkheading']); ?></h2>
                <p>
                    <?php echo $escape($pageTexts['bulkintro']); ?>
                </p>
            </div>
            <span class="contextgroups-count"><?php echo (int) $bulkTotal; ?></span>
        </div>

        <form method="get" action="index.php" class="contextgroups-search-form contextgroups-bulk-search">
            <input type="hidden" name="module" value="contextgroups" />
            <label for="contextgroups-bulk-search"><?php echo $escape($pageTexts['filteraccounts']); ?></label>
            <div class="contextgroups-search-controls">
                <input
                    type="search"
                    id="contextgroups-bulk-search"
                    name="bulksearch"
                    value="<?php echo $escape($bulkSearch); ?>"
                    maxlength="120"
                />
                <button type="submit" class="contextgroups-button contextgroups-button-primary"><?php echo $escape($pageTexts['filter']); ?></button>
                <?php if ($bulkSearch !== ''): ?>
                    <a class="contextgroups-button" href="index.php?module=contextgroups#contextgroups-bulk-students"><?php echo $escape($pageTexts['clear']); ?></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($bulkUsers === array()): ?>
            <p class="contextgroups-empty"><?php echo $escape($pageTexts['nofiltermatches']); ?></p>
        <?php else: ?>
            <form method="post" action="index.php" class="contextgroups-bulk-form">
                <input type="hidden" name="module" value="contextgroups" />
                <input type="hidden" name="action" value="bulkupdatestudents" />
                <input type="hidden" name="context" value="<?php echo $escape($contextCode); ?>" />
                <input type="hidden" name="membershiptoken" value="<?php echo $escape($membershipToken); ?>" />
                <input type="hidden" name="bulksearch" value="<?php echo $escape($bulkSearch); ?>" />
                <input type="hidden" name="bulkpage" value="<?php echo (int) $bulkPage; ?>" />

                <details class="contextgroups-account-table" id="contextgroups-account-table" open>
                    <summary class="contextgroups-button"><?php echo $escape($pageTexts['accounttabletoggle']); ?></summary>
                    <div class="contextgroups-table-wrap">
                    <table class="contextgroups-bulk-table">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo $escape($roles['student']['singular']); ?></th>
                                <th scope="col"><?php echo $escape($pageTexts['account']); ?></th>
                                <th scope="col"><?php echo $escape($pageTexts['currentcontextrole']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bulkUsers as $bulkUser): ?>
                                <?php
                                $bulkUserId = isset($bulkUser['userId'])
                                    ? (string) $bulkUser['userId']
                                    : '';
                                $bulkRoles = isset($bulkUser['courseRoles'])
                                    && is_array($bulkUser['courseRoles'])
                                    ? $bulkUser['courseRoles']
                                    : array();
                                $protected = !empty($bulkUser['protectLecturer']);
                                ?>
                                <tr>
                                    <td class="contextgroups-bulk-check">
                                        <input type="hidden" name="listedids[]" value="<?php echo $escape($bulkUserId); ?>" />
                                        <input
                                            type="checkbox"
                                            class="contextgroups-student-checkbox"
                                            id="student-<?php echo $escape($bulkUserId); ?>"
                                            name="studentids[]"
                                            value="<?php echo $escape($bulkUserId); ?>"
                                            <?php echo !empty($bulkUser['isStudent']) ? 'checked' : ''; ?>
                                            <?php echo $protected ? 'disabled' : ''; ?>
                                        />
                                        <label class="contextgroups-visually-hidden" for="student-<?php echo $escape($bulkUserId); ?>">
                                            <?php echo $escape($formatText($pageTexts['studentmembership'], array('NAME' => $displayName($bulkUser)))); ?>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="contextgroups-person">
                                            <strong><?php echo $escape($displayName($bulkUser)); ?></strong>
                                            <?php if (!empty($bulkUser['username'])): ?>
                                                <span>@<?php echo $escape($bulkUser['username']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($bulkUser['email'])): ?>
                                                <span><?php echo $escape($bulkUser['email']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($protected): ?>
                                            <span class="contextgroups-role-tag"><?php echo $escape($formatText($pageTexts['youraccount'], array('ROLE' => $roles['lecturer']['singular']))); ?></span>
                                        <?php elseif ($bulkRoles === array()): ?>
                                            <span class="contextgroups-muted"><?php echo $escape($pageTexts['notincontext']); ?></span>
                                        <?php else: ?>
                                            <?php foreach ($bulkRoles as $bulkRole): ?>
                                                <?php if (isset($roles[$bulkRole])): ?>
                                                    <span class="contextgroups-role-tag"><?php echo $escape($roles[$bulkRole]['singular']); ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>

                    <div class="contextgroups-bulk-actions">
                    <p><?php echo $escape(html_entity_decode($formatText($pageTexts['showing'], array('FIRST' => $bulkFirst, 'LAST' => $bulkLast, 'TOTAL' => $bulkTotal)), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></p>
                    <div>
                        <button type="button" class="contextgroups-button" id="contextgroups-select-displayed"><?php echo $escape($pageTexts['selectdisplayed']); ?></button>
                        <button type="button" class="contextgroups-button" id="contextgroups-clear-displayed"><?php echo $escape($pageTexts['cleardisplayed']); ?></button>
                        <button type="submit" class="contextgroups-button contextgroups-button-primary"><?php echo $escape($pageTexts['savedisplayed']); ?></button>
                    </div>
                    </div>
                </details>
            </form>
            <script>
            (function contextgroupsAccountTableState() {
                'use strict';
                var table = document.getElementById('contextgroups-account-table');
                if (!table || typeof window.sessionStorage === 'undefined') {
                    return;
                }
                var contextInput = document.querySelector(
                    '#contextgroups-account-table input[name="context"]'
                ) || document.querySelector('input[name="context"]');
                var contextCode = contextInput ? contextInput.value : '';
                var storageKey = 'contextgroups.accountTable.open.' + contextCode;
                try {
                    var storedState = window.sessionStorage.getItem(storageKey);
                    if (storedState !== null) {
                        table.open = storedState === '1';
                    }
                    table.addEventListener('toggle', function () {
                        window.sessionStorage.setItem(
                            storageKey,
                            table.open ? '1' : '0'
                        );
                    });
                } catch (error) {
                    // The disclosure remains fully usable when storage is unavailable.
                }
            }());
            </script>

            <?php if ($bulkPages > 1): ?>
                <nav class="contextgroups-pagination" aria-label="<?php echo $escape($pageTexts['accountpages']); ?>">
                    <?php if ($bulkPage > 1): ?>
                        <a class="contextgroups-button" href="<?php echo $escape($pageUrl($bulkPage - 1)); ?>"><?php echo $escape($pageTexts['previous']); ?></a>
                    <?php endif; ?>
                    <span><?php echo $escape($formatText($pageTexts['pageof'], array('PAGE' => $bulkPage, 'PAGES' => $bulkPages))); ?></span>
                    <?php if ($bulkPage < $bulkPages): ?>
                        <a class="contextgroups-button" href="<?php echo $escape($pageUrl($bulkPage + 1)); ?>"><?php echo $escape($pageTexts['next']); ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <div class="contextgroups-bulk-secondary">
            <section aria-labelledby="contextgroups-import-heading">
                <h3 id="contextgroups-import-heading"><?php echo $escape($pageTexts['uploadheading']); ?></h3>
                <p>
                    <?php echo $escape($pageTexts['uploadhelp']); ?>
                </p>
                <form method="post" action="index.php" enctype="multipart/form-data" class="contextgroups-stack-form">
                    <input type="hidden" name="module" value="contextgroups" />
                    <input type="hidden" name="action" value="importstudents" />
                    <input type="hidden" name="context" value="<?php echo $escape($contextCode); ?>" />
                    <input type="hidden" name="membershiptoken" value="<?php echo $escape($membershipToken); ?>" />
                    <label for="contextgroups-student-file"><?php echo $escape($pageTexts['csvfile']); ?></label>
                    <input type="file" id="contextgroups-student-file" name="studentfile" accept=".csv,text/csv" required />
                    <button type="submit" class="contextgroups-button contextgroups-button-primary"><?php echo $escape($pageTexts['uploadadd']); ?></button>
                </form>
            </section>

            <section class="contextgroups-danger-zone" aria-labelledby="contextgroups-remove-all-heading">
                <h3 id="contextgroups-remove-all-heading"><?php echo $escape($pageTexts['removeall']); ?></h3>
                <p><?php echo $escape($pageTexts['removeallhelp']); ?></p>
                <form method="post" action="index.php" class="contextgroups-stack-form">
                    <input type="hidden" name="module" value="contextgroups" />
                    <input type="hidden" name="action" value="removeallstudents" />
                    <input type="hidden" name="context" value="<?php echo $escape($contextCode); ?>" />
                    <input type="hidden" name="membershiptoken" value="<?php echo $escape($membershipToken); ?>" />
                    <label class="contextgroups-confirm-line">
                        <input type="checkbox" name="confirmremoveall" value="yes" required />
                        <?php echo $escape($pageTexts['removeallconfirm']); ?>
                    </label>
                    <button type="submit" class="contextgroups-button contextgroups-button-danger"><?php echo $escape($pageTexts['removeall']); ?></button>
                </form>
            </section>
        </div>
    </section>

    <section class="contextgroups-members" aria-labelledby="contextgroups-current-heading">
        <div class="contextgroups-section-heading">
            <div>
                <p class="contextgroups-eyebrow"><?php echo $escape($pageTexts['currentmembership']); ?></p>
                <h2 id="contextgroups-current-heading"><?php echo $escape($pageTexts['currentmembers']); ?></h2>
            </div>
        </div>

        <div class="contextgroups-role-grid">
            <?php foreach ($memberSections as $role => $section): ?>
                <section class="contextgroups-role-card" aria-labelledby="contextgroups-role-<?php echo $escape($role); ?>">
                    <div class="contextgroups-section-heading">
                        <h3 id="contextgroups-role-<?php echo $escape($role); ?>">
                            <?php echo $escape($section['label']); ?>
                        </h3>
                        <span class="contextgroups-count" aria-label="<?php echo $escape($formatText($pageTexts['membercount'], array('COUNT' => $section['count']))); ?>">
                            <?php echo (int) $section['count']; ?>
                        </span>
                    </div>

                    <?php if ($section['members'] === array()): ?>
                        <p class="contextgroups-empty"><?php echo $escape($formatText($pageTexts['norole'], array('ROLE' => $section['label']))); ?></p>
                    <?php else: ?>
                        <ul class="contextgroups-member-list">
                            <?php foreach ($section['members'] as $member): ?>
                                <?php
                                $memberUserId = isset($member['userId'])
                                    ? (string) $member['userId']
                                    : '';
                                $isCurrentUser = $memberUserId === (string) $currentUserId;
                                $protectLecturer = $role === 'lecturer' && $isCurrentUser;
                                ?>
                                <li>
                                    <div class="contextgroups-person">
                                        <strong><?php echo $escape($displayName($member)); ?></strong>
                                        <?php if (!empty($member['username'])): ?>
                                            <span>@<?php echo $escape($member['username']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($member['email'])): ?>
                                            <span><?php echo $escape($member['email']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($isCurrentUser): ?>
                                            <span class="contextgroups-you"><?php echo $escape($pageTexts['you']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$protectLecturer): ?>
                                        <form method="post" action="index.php">
                                            <input type="hidden" name="module" value="contextgroups" />
                                            <input type="hidden" name="action" value="removeuser" />
                                            <input type="hidden" name="context" value="<?php echo $escape($contextCode); ?>" />
                                            <input type="hidden" name="membershiptoken" value="<?php echo $escape($membershipToken); ?>" />
                                            <input type="hidden" name="userid" value="<?php echo $escape($memberUserId); ?>" />
                                            <input type="hidden" name="role" value="<?php echo $escape($role); ?>" />
                                            <button type="submit" class="contextgroups-button contextgroups-button-remove"><?php echo $escape($pageTexts['remove']); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!empty($section['truncated'])): ?>
                            <p class="contextgroups-search-hint">
                                <?php echo $escape($formatText($pageTexts['showingfirst'], array('FIRST' => count($section['members']), 'TOTAL' => $section['count']))); ?>
                                <?php if ($role === 'student'): ?>
                                    <?php echo $escape($pageTexts['usebulk']); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<script>
(function () {
    'use strict';
    var checkboxes = function () {
        return document.querySelectorAll('.contextgroups-student-checkbox:not(:disabled)');
    };
    var setDisplayed = function (checked) {
        var items = checkboxes();
        for (var index = 0; index < items.length; index += 1) {
            items[index].checked = checked;
        }
    };
    var selectButton = document.getElementById('contextgroups-select-displayed');
    var clearButton = document.getElementById('contextgroups-clear-displayed');
    if (selectButton) {
        selectButton.addEventListener('click', function () { setDisplayed(true); });
    }
    if (clearButton) {
        clearButton.addEventListener('click', function () { setDisplayed(false); });
    }
}());
</script>
