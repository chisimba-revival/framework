<?php
/**
 * Native semantic batch user registration interface.
 *
 * @category  Chisimba
 * @package   useradmin
 * @author    Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$lang = function ($key) use ($escape) {
    return $escape($this->objLanguage->languageText(
        'mod_useradmin_' . $key,
        'useradmin'
    ));
};
$reason = function ($code) use ($lang) {
    $known = array(
        'upload_failed', 'invalid_file_type', 'file_unreadable',
        'file_too_large', 'empty_file', 'too_many_rows',
        'invalid_headers', 'missing_required_header', 'preview_failed',
        'preview_expired', 'missing_username', 'missing_firstname',
        'missing_surname', 'missing_emailaddress', 'invalid_emailaddress',
        'missing_registration_type', 'registration_type_not_permitted',
        'missing_password', 'duplicate_username_in_file',
        'duplicate_email_in_file', 'username_taken', 'email_taken',
        'username_generation_failed', 'user_created', 'invalid_password',
        'userid_allocation_failed',
        'create_failed'
    );
    $code = (string) $code;
    return $lang('batch_' . (in_array($code, $known, true)
        ? $code : 'create_failed'));
};
$registrationType = function ($type) use ($lang) {
    return $lang('batchtype_' . ($type === 'student' ? 'student' : 'guest'));
};
$preview = isset($batchUserPreview) && is_array($batchUserPreview)
    ? $batchUserPreview : array();
$result = isset($batchUserResult) && is_array($batchUserResult)
    ? $batchUserResult : array();
$csrf = isset($batchUserCsrfToken) ? (string) $batchUserCsrfToken : '';
$error = isset($batchUserError) ? (string) $batchUserError : '';
$cssUrl = $this->getResourceUri('css/native-admin.css', 'useradmin');
$directoryUrl = $this->uri(array('action' => 'native'), 'useradmin');
?>
<link rel="stylesheet" href="<?php echo $escape($cssUrl); ?>">
<main class="ua-admin" aria-labelledby="ua-batch-title">
    <header class="ua-page-header">
        <div>
            <h1 id="ua-batch-title"><?php echo $lang('batchtitle'); ?></h1>
            <p><?php echo $lang('batchintro'); ?></p>
        </div>
        <a class="ua-button" href="<?php echo $escape($directoryUrl); ?>"><?php echo $lang('batchback'); ?></a>
    </header>

    <?php if ($error !== ''): ?>
        <div class="ua-notice ua-notice-error" role="alert"><?php echo $reason($error); ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
        <section class="ua-panel" aria-labelledby="ua-result-title">
            <h2 id="ua-result-title"><?php echo $lang('batchcompleted'); ?></h2>
            <p><?php echo $lang('batchidentifier'); ?>: <?php echo $escape($result['batchId']); ?></p>
            <p><?php echo $lang('batchcreatedcount'); ?>: <?php echo $escape($result['createdCount']); ?> · <?php echo $lang('batchskippedcount'); ?>: <?php echo $escape($result['skippedCount']); ?> · <?php echo $lang('batchfailedcount'); ?>: <?php echo $escape($result['failedCount']); ?></p>
            <div class="ua-table-wrap"><table>
                <thead><tr><th><?php echo $lang('batchrow'); ?></th><th><?php echo $escape($this->objLanguage->languageText('word_username')); ?></th><th><?php echo $escape($this->objLanguage->languageText('phrase_emailaddress')); ?></th><th><?php echo $lang('batchtype'); ?></th><th><?php echo $lang('batchresult'); ?></th></tr></thead>
                <tbody><?php foreach ($result['results'] as $row): ?><tr>
                    <td><?php echo $escape($row['line']); ?></td><td><?php echo $escape($row['username']); ?></td><td><?php echo $escape($row['emailaddress']); ?></td><td><?php echo $registrationType($row['registrationType']); ?></td><td><?php echo $reason($row['code']); ?></td>
                </tr><?php endforeach; ?></tbody>
            </table></div>
            <p class="ua-help"><strong><?php echo $lang('batchposthook'); ?>:</strong> <?php echo $lang('batchhookavailable'); ?></p>
            <a class="ua-button ua-button-primary" href="<?php echo $this->uri(array('action' => 'batchimport'), 'useradmin'); ?>"><?php echo $lang('batchnewupload'); ?></a>
        </section>
    <?php elseif ($preview): ?>
        <section class="ua-panel" aria-labelledby="ua-preview-title">
            <h2 id="ua-preview-title"><?php echo $lang('batchpreview'); ?></h2>
            <p><?php echo $lang('batchfilename'); ?>: <?php echo $escape($preview['sourceName']); ?> · <?php echo $lang('batchidentifier'); ?>: <?php echo $escape($preview['batchId']); ?></p>
            <p><?php echo $lang('batchvalidcount'); ?>: <?php echo $escape($preview['validCount']); ?> · <?php echo $lang('batchrejectedcount'); ?>: <?php echo $escape($preview['rejectedCount']); ?></p>
            <div class="ua-table-wrap"><table>
                <thead><tr><th><?php echo $lang('batchrow'); ?></th><th><?php echo $escape($this->objLanguage->languageText('word_username')); ?></th><th><?php echo $escape($this->objLanguage->languageText('phrase_emailaddress')); ?></th><th><?php echo $lang('batchtype'); ?></th><th><?php echo $lang('batchresult'); ?></th></tr></thead>
                <tbody><?php foreach ($preview['rows'] as $row): ?><tr>
                    <td><?php echo $escape($row['line']); ?></td><td><?php echo $escape($row['data']['username']); ?><?php if (!empty($row['usernameGenerated'])): ?> <span class="ua-help">(<?php echo $lang('batchusernamegenerated'); ?>)</span><?php endif; ?></td><td><?php echo $escape($row['data']['emailaddress']); ?></td><td><?php echo $registrationType($row['data']['registration_type']); ?></td><td><?php echo $row['valid'] ? $lang('batchvalid') : $reason($row['errors'][0]); ?></td>
                </tr><?php endforeach; ?></tbody>
            </table></div>
            <div class="ua-form-actions">
                <?php if ((int) $preview['validCount'] > 0): ?><form method="post" action="<?php echo $this->uri(array('action' => 'batchconfirm'), 'useradmin'); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($csrf); ?>"><button class="ua-button ua-button-primary" type="submit"><?php echo $lang('batchconfirm'); ?></button></form><?php endif; ?>
                <form method="post" action="<?php echo $this->uri(array('action' => 'batchcancel'), 'useradmin'); ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($csrf); ?>"><button class="ua-button" type="submit"><?php echo $lang('batchcancel'); ?></button></form>
            </div>
        </section>
    <?php else: ?>
        <section class="ua-panel">
            <div class="ua-batch-guidance" id="ua-batch-file-help">
                <p><?php echo $lang('batchrequiredcolumns'); ?></p>
                <p><?php echo $lang('batchoptionalcolumns'); ?></p>
                <p><?php echo $lang('batchpolicy'); ?></p>
            </div>
            <form class="ua-batch-upload" method="post" enctype="multipart/form-data" action="<?php echo $this->uri(array('action' => 'batchpreview'), 'useradmin'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrf); ?>">
                <div class="ua-file-field">
                    <label for="ua-batch-file"><?php echo $lang('batchfile'); ?></label>
                    <input id="ua-batch-file" type="file" name="userfile" accept=".csv,text/csv" aria-describedby="ua-batch-file-help" required>
                </div>
                <div class="ua-form-actions"><button class="ua-button ua-button-primary" type="submit"><?php echo $lang('batchupload'); ?></button></div>
            </form>
        </section>
    <?php endif; ?>
</main>
