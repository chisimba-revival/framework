<?php
/**
 * Native semantic user administration interface.
 *
 * @category  Chisimba
 * @package   useradmin
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$records = isset($userAdminRecords) && is_array($userAdminRecords)
    ? $userAdminRecords : array();
$selected = isset($userAdminSelected) && is_array($userAdminSelected)
    ? $userAdminSelected : null;
$query = isset($userAdminQuery) ? (string) $userAdminQuery : '';
$page = isset($userAdminPage) ? (int) $userAdminPage : 1;
$pages = isset($userAdminPages) ? (int) $userAdminPages : 1;
$limit = isset($userAdminLimit) ? (int) $userAdminLimit : 25;
$total = isset($userAdminTotal) ? (int) $userAdminTotal : 0;
$titles = isset($userAdminTitles) && is_array($userAdminTitles)
    ? $userAdminTitles : array('');
$countries = isset($userAdminCountries) && is_array($userAdminCountries)
    ? $userAdminCountries : array();
$defaultCountry = isset($userAdminDefaultCountry)
    ? (string) $userAdminDefaultCountry : '';
$csrf = isset($userAdminCsrfToken) ? (string) $userAdminCsrfToken : '';
$message = isset($userAdminMessage) ? (string) $userAdminMessage : '';
$error = isset($userAdminError) ? (string) $userAdminError : '';
$base = array('action' => 'native', 'q' => $query, 'limit' => $limit);
$url = function (array $changes) use ($base) {
    return html_entity_decode(
        $this->uri(array_merge($base, $changes), 'useradmin'),
        ENT_QUOTES,
        'UTF-8'
    );
};
$value = function ($key, $fallback = '') use ($selected) {
    return $selected !== null && isset($selected[$key])
        ? $selected[$key] : $fallback;
};
$cssUrl = $this->getResourceUri('css/native-admin.css', 'useradmin');
?>
<link rel="stylesheet" href="<?php echo $escape($cssUrl); ?>">

<main class="ua-admin" aria-labelledby="ua-title">
    <header class="ua-page-header">
        <div>
            <p class="ua-eyebrow">Administration</p>
            <h1 id="ua-title">Users</h1>
            <p>Manage local user profiles and account status.</p>
        </div>
        <div class="ua-form-actions">
            <a class="ua-button" href="<?php echo $this->uri(array('action' => 'batchimport'), 'useradmin'); ?>"><?php echo $escape($this->objLanguage->languageText('mod_useradmin_batchregister', 'useradmin')); ?></a>
            <a class="ua-button ua-button-primary" href="<?php echo $escape($url(array('userid' => '', 'page' => $page))); ?>#ua-editor"><?php echo $escape($this->objLanguage->languageText('mod_useradmin_adduseraction', 'useradmin')); ?></a>
        </div>
    </header>

    <?php if ($message !== ''): ?>
        <div class="ua-notice ua-notice-success" role="status"><?php echo $escape(str_replace('_', ' ', $message)); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="ua-notice ua-notice-error" role="alert"><?php echo $escape(str_replace('_', ' ', $error)); ?></div>
    <?php endif; ?>

    <section class="ua-panel" aria-labelledby="ua-list-title">
        <div class="ua-panel-header">
            <div>
                <h2 id="ua-list-title">User directory</h2>
                <p><?php echo $escape($total); ?> account<?php echo $total === 1 ? '' : 's'; ?></p>
            </div>
            <form class="ua-search" method="get" action="">
                <input type="hidden" name="module" value="useradmin">
                <input type="hidden" name="action" value="native">
                <label for="ua-query">Search users</label>
                <div>
                    <input id="ua-query" name="q" type="search" value="<?php echo $escape($query); ?>" placeholder="Name, username or email">
                    <button class="ua-button" type="submit">Search</button>
                </div>
            </form>
        </div>

        <?php
        $batchArchiveAction = html_entity_decode(
            $this->uri(array('action' => 'batcharchive'), 'useradmin'),
            ENT_QUOTES,
            'UTF-8'
        );
        $setStatusAction = html_entity_decode(
            $this->uri(array('action' => 'setstatus'), 'useradmin'),
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
        <form class="ua-batch-actions" method="post" action="<?php echo $escape($batchArchiveAction); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrf); ?>">
            <input type="hidden" name="q" value="<?php echo $escape($query); ?>">
            <input type="hidden" name="page" value="<?php echo $escape($page); ?>">
            <input type="hidden" name="limit" value="<?php echo $escape($limit); ?>">
            <input type="hidden" name="active" value="0">
            <div class="ua-batch-controls">
                <label for="ua-batch-action">Selected accounts</label>
                <select id="ua-batch-action" name="batch_action" required>
                    <option value="">Choose an action</option>
                    <option value="archive">Archive accounts</option>
                </select>
                <button class="ua-button" type="submit">Apply</button>
            </div>

        <div class="ua-table-wrap">
            <table>
                <caption class="ua-visually-hidden">User accounts</caption>
                <thead>
                    <tr><th scope="col"><span class="ua-visually-hidden">Select</span></th><th scope="col">Name</th><th scope="col">Username</th><th scope="col">Email</th><th scope="col">Status</th><th scope="col"><span class="ua-visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                <?php if (!$records): ?>
                    <tr><td colspan="6" class="ua-empty">No users match this view.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <?php
                        $userId = isset($record['userid']) ? $record['userid'] : '';
                        $name = trim(
                            (isset($record['firstname']) ? $record['firstname'] : '')
                            . ' '
                            . (isset($record['surname']) ? $record['surname'] : '')
                        );
                        $active = !empty($record['isactive']);
                        ?>
                        <tr>
                            <td data-label="Select"><input type="checkbox" name="userids[]" value="<?php echo $escape($userId); ?>" aria-label="Select <?php echo $escape($name !== '' ? $name : 'this account'); ?>"<?php echo $userId === (string) $this->objUser->userId() ? ' disabled' : ''; ?>></td>
                            <td data-label="Name"><?php echo $escape($name !== '' ? $name : 'Unnamed user'); ?></td>
                            <td data-label="Username"><?php echo $escape(isset($record['username']) ? $record['username'] : ''); ?></td>
                            <td data-label="Email"><?php echo $escape(isset($record['emailaddress']) ? $record['emailaddress'] : ''); ?></td>
                            <td data-label="Status"><span class="ua-status <?php echo $active ? 'is-active' : 'is-inactive'; ?>"><?php echo $active ? 'Active' : 'Archived'; ?></span></td>
                            <td class="ua-actions"><a href="<?php echo $escape($url(array('userid' => $userId, 'page' => $page))); ?>#ua-editor">Edit</a><?php if ($active && $userId !== (string) $this->objUser->userId()): ?><button class="ua-action-link ua-action-danger" type="submit" formnovalidate formaction="<?php echo $escape($setStatusAction); ?>" name="userid" value="<?php echo $escape($userId); ?>">Archive</button><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        </form>

        <?php if ($pages > 1): ?>
            <nav class="ua-pagination" aria-label="User directory pages">
                <?php if ($page > 1): ?><a href="<?php echo $escape($url(array('page' => $page - 1))); ?>">Previous</a><?php endif; ?>
                <span>Page <?php echo $escape($page); ?> of <?php echo $escape($pages); ?></span>
                <?php if ($page < $pages): ?><a href="<?php echo $escape($url(array('page' => $page + 1))); ?>">Next</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <section id="ua-editor" class="ua-panel ua-editor" aria-labelledby="ua-editor-title">
        <div class="ua-panel-header">
            <div>
                <h2 id="ua-editor-title"><?php echo $selected ? 'Edit user' : 'Add user'; ?></h2>
                <p><?php echo $selected ? 'Update the profile or account status.' : 'Create a local account with an initial Guest membership.'; ?></p>
            </div>
            <?php if ($selected): ?><a href="<?php echo $escape($url(array('userid' => '', 'page' => $page))); ?>#ua-editor">Cancel edit</a><?php endif; ?>
        </div>

        <?php
        $editAction = html_entity_decode(
            $this->uri(
                array('action' => $selected ? 'update' : 'create'),
                'useradmin'
            ),
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
        <form method="post" action="<?php echo $escape($editAction); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrf); ?>">
            <input type="hidden" name="userid" value="<?php echo $escape($value('userid')); ?>">
            <input type="hidden" name="q" value="<?php echo $escape($query); ?>">
            <input type="hidden" name="page" value="<?php echo $escape($page); ?>">
            <input type="hidden" name="limit" value="<?php echo $escape($limit); ?>">

            <fieldset>
                <legend>Profile</legend>
                <div class="ua-form-grid">
                    <label>Title
                        <select name="title">
                            <?php foreach ($titles as $title): ?>
                                <option value="<?php echo $escape($title); ?>"<?php echo (string) $value('title') === (string) $title ? ' selected' : ''; ?>><?php echo $escape($title === '' ? 'No title' : $title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span class="ua-field-label">First name <span class="ua-required" aria-hidden="true">*</span></span><input name="firstname" required value="<?php echo $escape($value('firstname')); ?>"></label>
                    <label><span class="ua-field-label">Surname <span class="ua-required" aria-hidden="true">*</span></span><input name="surname" required value="<?php echo $escape($value('surname')); ?>"></label>
                    <label><span class="ua-field-label">Email address <span class="ua-required" aria-hidden="true">*</span></span><input name="emailaddress" type="email" required value="<?php echo $escape($value('emailaddress')); ?>"></label>
                    <label>Staff or student number<input name="staffnumber" value="<?php echo $escape($value('staffnumber')); ?>"></label>
                    <label>Cell number<input name="cellnumber" value="<?php echo $escape($value('cellnumber')); ?>"></label>
                    <label><?php echo $escape($this->objLanguage->languageText('word_country')); ?>
                        <?php
                        $countryValue = strtoupper((string) $value('country'));
                        if ($selected === null && $countryValue === '') {
                            $countryValue = $defaultCountry;
                        }
                        ?>
                        <select name="country">
                            <option value=""><?php echo $escape($this->objLanguage->languageText('mod_useradmin_countrynotspecified', 'useradmin')); ?></option>
                            <?php foreach ($countries as $countryCode => $countryName): ?>
                                <option value="<?php echo $escape($countryCode); ?>"<?php echo $countryValue === $countryCode ? ' selected' : ''; ?>><?php echo $escape($countryName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Sex<select name="sex"><option value="">Not specified</option><option value="M"<?php echo $value('sex') === 'M' ? ' selected' : ''; ?>>Male</option><option value="F"<?php echo $value('sex') === 'F' ? ' selected' : ''; ?>>Female</option></select></label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Account</legend>
                <div class="ua-form-grid">
                    <label><span class="ua-field-label">Username <span class="ua-required" aria-hidden="true">*</span></span><input name="username" required autocomplete="off" value="<?php echo $escape($value('username')); ?>"></label>
                    <label><span class="ua-field-label">Password <?php if (!$selected): ?><span class="ua-required" aria-hidden="true">*</span><?php endif; ?></span><input name="password" type="password"<?php echo !$selected ? ' required' : ''; ?> autocomplete="new-password"></label>
                    <label><span class="ua-field-label">Repeat password <?php if (!$selected): ?><span class="ua-required" aria-hidden="true">*</span><?php endif; ?></span><input name="repeat_password" type="password"<?php echo !$selected ? ' required' : ''; ?> autocomplete="new-password"></label>
                    <label class="ua-checkbox"><input name="isactive" type="checkbox" value="1"<?php echo !$selected || !empty($selected['isactive']) ? ' checked' : ''; ?>> Active account</label>
                </div>
            </fieldset>

            <div class="ua-form-actions"><button class="ua-button ua-button-primary" type="submit"><?php echo $selected ? 'Save changes' : 'Create user'; ?></button></div>
        </form>

        <?php if ($selected): ?>
            <?php $isActive = !empty($selected['isactive']); ?>
            <?php
            $statusAction = html_entity_decode(
                $this->uri(array('action' => 'setstatus'), 'useradmin'),
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
            <form class="ua-status-form" method="post" action="<?php echo $escape($statusAction); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrf); ?>">
                <input type="hidden" name="userid" value="<?php echo $escape($value('userid')); ?>">
                <input type="hidden" name="active" value="<?php echo $isActive ? '0' : '1'; ?>">
                <input type="hidden" name="q" value="<?php echo $escape($query); ?>">
                <input type="hidden" name="page" value="<?php echo $escape($page); ?>">
                <input type="hidden" name="limit" value="<?php echo $escape($limit); ?>">
                <button class="ua-button <?php echo $isActive ? 'ua-button-danger' : ''; ?>" type="submit"><?php echo $isActive ? 'Deactivate account' : 'Activate account'; ?></button>
            </form>
        <?php endif; ?>
    </section>
</main>
