<?php

/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_START */
$fmText = function ($code) {
    return $this->objLanguage->languageText('mod_filemanager_native_' . $code, 'filemanager');
};
/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_END */
/* NATIVE_FILEMANAGER_URI_HELPER_START */
$fmUri = function ($params = array(), ...$ignored) {
    return htmlspecialchars_decode($this->uri($params, 'filemanager'), ENT_QUOTES);
};
/* NATIVE_FILEMANAGER_URI_HELPER_END */

/**
 * Native home/folder browser.
 *
 * This is presentation-only. Existing Chisimba services remain authoritative
 * for storage, permissions, quotas, uploads, downloads and metadata.
 */

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$folders = isset($subfolders) && is_array($subfolders) ? $subfolders : array();
$fileItems = isset($files) && is_array($files) ? $files : array();
$canManage = !empty($folderPermission);
$isRoot = isset($folder['folderlevel']) && (int) $folder['folderlevel'] === 2;

$message = (string) $this->getParam('message');
$error = (string) $this->getParam('error');

$messages = array(
    'foldercreated' => $fmText('folder_created'),
    'filesdeleted' => $fmText('files_deleted'),
    'folderdeleted' => $fmText('folder_deleted'),
);
$errors = array(
    'nofoldernameprovided' => $fmText('enter_folder_name'),
    'illegalcharacters' => $fmText('illegal_folder_chars'),
    'couldnotcreatefolder' => $fmText('create_folder_failed'),
    'couldnotdeletefile' => $fmText('delete_file_failed'),
    'couldnotdeletefolder' => $fmText('delete_folder_failed'),
    'delete_not_permitted' => $fmText('delete_forbidden'),
    'cannotdeleterootfolder' => $fmText('delete_root_forbidden'),
    'delete_requires_post' => $fmText('delete_requires_post'),
    'nofilesconfirmedfordelete' => $fmText('select_delete_files'),
);

$folderName = isset($folder['folderpath'])
    ? basename($folder['folderpath'])
    : $fmText('files');

$parentPath = isset($folder['folderpath'])
    ? dirname($folder['folderpath'])
    : '';
$parentId = ($parentPath !== '' && $parentPath !== '.')
    ? $this->objFolders->getFolderId($parentPath)
    : false;

$formatBytes = static function ($bytes) {
    $bytes = max(0, (int) $bytes);
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $index = 0;
    $value = (float) $bytes;
    while ($value >= 1024 && $index < count($units) - 1) {
        $value /= 1024;
        $index++;
    }
    return ($index === 0 ? (string) (int) $value : number_format($value, 1))
        . ' ' . $units[$index];
};

$fmIcon = static function ($kind) {
    $paths = array(
        'folder' => '<path d="M3 6.5h6l2 2H21v10.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M3 9h18"/>',
        'pdf' => '<path d="M6 2.5h8l4 4V21H6z"/><path d="M14 2.5v5h4"/><path d="M8.5 16.5v-5h1.8a1.5 1.5 0 0 1 0 3H8.5m5.1 2v-5h1.3c1.7 0 2.6 1 2.6 2.5s-.9 2.5-2.6 2.5z"/>',
        'audio' => '<path d="M9 18V6l10-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="16.5" cy="16" r="2.5"/>',
        'video' => '<rect x="3" y="5" width="14" height="14" rx="2"/><path d="m17 10 4-2v8l-4-2z"/><path d="m9 9 4 3-4 3z"/>',
        'archive' => '<path d="M6 2.5h12V21H6z"/><path d="M10 3v3h4V3m-4 6h4m-4 3h4m-4 3h4"/><rect x="10" y="17" width="4" height="2"/>',
        'document' => '<path d="M6 2.5h8l4 4V21H6z"/><path d="M14 2.5v5h4M9 11h6M9 14h6M9 17h4"/>',
        'spreadsheet' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M4 8h16M4 13h16M4 17h16M10 8v13M15 8v13"/>',
        'presentation' => '<rect x="4" y="4" width="16" height="12" rx="1"/><path d="M12 16v5m-4 0 4-5 4 5"/><path d="M8 12V8m4 4V6m4 6v-2"/>',
        'text' => '<path d="M6 2.5h8l4 4V21H6z"/><path d="M14 2.5v5h4M9 11h6M9 14h6M9 17h6"/>',
        'code' => '<path d="M6 2.5h8l4 4V21H6z"/><path d="M14 2.5v5h4m-7 5-2 2 2 2m3-4 2 2-2 2"/>',
        'file' => '<path d="M6 2.5h8l4 4V21H6z"/><path d="M14 2.5v5h4"/>',
    );
    $path = isset($paths[$kind]) ? $paths[$kind] : $paths['file'];
    return '<svg class="fm-filetype-svg fm-filetype-' .
        htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') .
        '" viewBox="0 0 24 24" focusable="false" aria-hidden="true">' .
        $path . '</svg>';
};

$fileIcon = static function ($file) use ($fmIcon) {
    $mime = isset($file['mimetype']) ? strtolower((string) $file['mimetype']) : '';
    $type = isset($file['datatype'])
        ? strtolower(ltrim((string) $file['datatype'], '.'))
        : '';

    if (strpos($mime, 'audio/') === 0 ||
            in_array($type, array('mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac'), true)) {
        return $fmIcon('audio');
    }
    if (strpos($mime, 'video/') === 0 ||
            in_array($type, array('mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv', 'wmv'), true)) {
        return $fmIcon('video');
    }
    if ($type === 'pdf' || $mime === 'application/pdf') {
        return $fmIcon('pdf');
    }
    if (in_array($type, array('zip', 'tar', 'gz', 'tgz', 'bz2', 'rar', '7z'), true)) {
        return $fmIcon('archive');
    }
    if (in_array($type, array('doc', 'docx', 'odt', 'rtf'), true)) {
        return $fmIcon('document');
    }
    if (in_array($type, array('xls', 'xlsx', 'ods', 'csv'), true)) {
        return $fmIcon('spreadsheet');
    }
    if (in_array($type, array('ppt', 'pptx', 'odp'), true)) {
        return $fmIcon('presentation');
    }
    if (in_array($type, array('php', 'js', 'css', 'html', 'htm', 'xml', 'json', 'py', 'sh'), true)) {
        return $fmIcon('code');
    }
    if (strpos($mime, 'text/') === 0 ||
            in_array($type, array('txt', 'md', 'log'), true)) {
        return $fmIcon('text');
    }
    return $fmIcon('file');
};
?>

<nav class="fm-breadcrumbs" aria-label="<?php echo $escape($fmText('file_location')); ?>">
    <?php echo isset($breadcrumbs) ? $breadcrumbs : ''; ?>
</nav>

<?php
/* NATIVE_ARCHIVE_ERROR_START */
$archiveError = (string) $this->getParam('archiveerror');
/* NATIVE_ARCHIVE_ERROR_END */
?>
<?php if ($archiveError !== ''): ?>
    <div class="fm-notice fm-notice-error" role="alert">
        <?php echo $escape($archiveError); ?>
    </div>
<?php endif; ?>

<?php if (isset($messages[$message])): ?>
    <div class="fm-notice fm-notice-success" role="status">
        <?php echo $escape($messages[$message]); ?>
    </div>
<?php endif; ?>

<?php if (isset($errors[$error])): ?>
    <div class="fm-notice fm-notice-error" role="alert">
        <?php echo $escape($errors[$error]); ?>
    </div>
<?php endif; ?>

<div class="fm-toolbar" aria-label="<?php echo $escape($fmText('folder_actions')); ?>">
    <div class="fm-toolbar-primary">
        <?php if ($parentId): ?>
            <a class="fm-button fm-button-secondary"
               href="<?php echo $escape($fmUri(array(
                   'action' => 'viewfolder',
                   'folder' => $parentId,
               ))); ?>">
                ← <?php echo $escape($fmText('up_one_level')); ?>
            </a>
        <?php endif; ?>

        <?php if ($canManage): ?>
            <button class="fm-button"
                    type="button"
                    data-fm-toggle="fm-create-folder">
                New folder
            </button>

            <?php if (!$isRoot): ?>
                <button class="fm-button fm-button-secondary"
                        type="button"
                        data-fm-toggle="fm-rename-folder">
                    Rename folder
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="fm-view-controls" aria-label="<?php echo $escape($fmText('view_options')); ?>">
        <button type="button"
                class="fm-icon-button"
                data-fm-view="grid"
                aria-pressed="true">
            Grid
        </button>
        <button type="button"
                class="fm-icon-button"
                data-fm-view="list"
                aria-pressed="false">
            List
        </button>
    </div>
</div>

<?php if ($canManage): ?>
    <section id="fm-create-folder"
             class="fm-action-panel"
             hidden
             aria-labelledby="fm-create-folder-heading">
        <h2 id="fm-create-folder-heading"><?php echo $escape($fmText('create_folder')); ?></h2>
        <form method="post"
              action="<?php echo $escape($fmUri(array('action' => 'createfolder'))); ?>">
            <input type="hidden"
                   name="parentfolder"
                   value="<?php echo $escape($folderId); ?>" />
            <label for="fm-folder-name"><?php echo $escape($fmText('folder_name')); ?></label>
            <div class="fm-inline-form">
                <input id="fm-folder-name"
                       name="foldername"
                       type="text"
                       required
                       maxlength="150"
                       autocomplete="off" />
                <button type="submit"><?php echo $escape($fmText('create')); ?></button>
            </div>
        </form>
    </section>

    <?php if (!$isRoot): ?>
        <section id="fm-rename-folder"
                 class="fm-action-panel"
                 hidden
                 aria-labelledby="fm-rename-folder-heading">
            <h2 id="fm-rename-folder-heading"><?php echo $escape($fmText('rename_folder')); ?></h2>
            <form method="post"
                  action="<?php echo $escape($fmUri(array('action' => 'renamefolder'))); ?>">
                <input type="hidden"
                       name="folder"
                       value="<?php echo $escape($folderId); ?>" />
                <label for="fm-rename-folder-name"><?php echo $escape($fmText('new_name')); ?></label>
                <div class="fm-inline-form">
                    <input id="fm-rename-folder-name"
                           name="foldername"
                           type="text"
                           required
                           maxlength="150"
                           value="<?php echo $escape($folderName); ?>"
                           autocomplete="off" />
                    <button type="submit"><?php echo $escape($fmText('rename')); ?></button>
                </div>
            </form>
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="fm-browser" aria-labelledby="fm-browser-heading">
    <div class="fm-section-heading">
        <div>
            <h2 id="fm-browser-heading"><?php echo $escape($folderName); ?></h2>
            <p>
                <?php echo count($folders); ?>
                <?php echo $escape($fmText(count($folders) === 1 ? 'folder_singular' : 'folder_plural')); ?>,
                <?php echo count($fileItems); ?>
                <?php echo $escape($fmText(count($fileItems) === 1 ? 'file_singular' : 'file_plural')); ?>
            </p>
        </div>
    </div>

    <?php if (!$folders && !$fileItems): ?>
        <div class="fm-empty">
            <span class="fm-empty-icon"><?php echo $fmIcon('folder'); ?></span>
            <h3><?php echo $escape($fmText('empty_folder')); ?></h3>
            <p><?php echo $escape($fmText('empty_folder_help')); ?></p>
        </div>
    <?php else: ?>
        <!-- NATIVE_BULK_FILE_SELECTION_V3 -->
        <?php if ($canManage && $fileItems): ?>
            <form id="fm-bulk-delete-form"
                  method="post"
                  action="<?php echo $escape($fmUri(array(
                      'action' => 'multideleteconfirm',
                  ))); ?>">
                <input type="hidden"
                       name="folder"
                       value="<?php echo $escape($folderId); ?>" />
            </form>
            <div class="fm-bulk-actions" aria-label="<?php echo $escape($fmText('selection_actions')); ?>">
                <button type="button" id="fm-select-all"><?php echo $escape($fmText('select_all')); ?></button>
                <button type="button" id="fm-clear-selection"><?php echo $escape($fmText('clear')); ?></button>
                <span id="fm-selection-count"
                      role="status"
                      aria-live="polite"><?php echo $escape($fmText('files_selected')); ?></span>
                <button type="submit"
                        form="fm-bulk-delete-form"
                        id="fm-delete-selected"
                        class="fm-danger-button"
                        disabled>
                    <?php echo $escape($fmText('delete_selected')); ?>
                </button>
            </div>
        <?php endif; ?>

        <div id="fm-items" class="fm-items fm-items-grid">
            <?php foreach ($folders as $subfolder): ?>
                <?php
                if (!is_array($subfolder) || empty($subfolder['id'])) {
                    continue;
                }
                $subfolderPath = isset($subfolder['folderpath'])
                    ? (string) $subfolder['folderpath']
                    : '';
                $subfolderName = $subfolderPath !== ''
                    ? basename($subfolderPath)
                    : 'Folder';
                ?>
                <article class="fm-item fm-folder-item">
                    <a class="fm-item-main"
                       href="<?php echo $escape($fmUri(array(
                           'action' => 'viewfolder',
                           'folder' => $subfolder['id'],
                       ))); ?>">
                        <span class="fm-item-icon"><?php echo $fmIcon('folder'); ?></span>
                        <span class="fm-item-text">
                            <strong><?php echo $escape($subfolderName); ?></strong>
                            <span><?php echo $escape($fmText('folder')); ?></span>
                        </span>
                    </a>
                    <?php if ($canManage): ?>
                        <form class="fm-delete-form"
                              method="post"
                              action="<?php echo $escape($fmUri(array(
                                  'action' => 'deletefolder',
                                  'id' => $subfolder['id'],
                              ))); ?>"
                              onsubmit="return window.confirm(<?php echo json_encode($fmText('confirm_delete_folder'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);">
                            <button type="submit" class="fm-delete-button"><?php echo $escape($fmText('delete_folder')); ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>

            <?php foreach ($fileItems as $file): ?>
                <?php
                if (!is_array($file) || empty($file['id']) || empty($file['filename'])) {
                    continue;
                }

                $params = array(
                    'action' => 'file',
                    'id' => $file['id'],
                    'filename' => $file['filename'],
                );
                if (!empty($file['datatype'])) {
                    $params['type'] = '.' . ltrim((string) $file['datatype'], '.');
                }

                $openUrl = $fmUri($params);
                $infoUrl = $fmUri(array(
                    'action' => 'fileinfo',
                    'id' => $file['id'],
                ));
                $mimeType = isset($file['mimetype'])
                    ? strtolower((string) $file['mimetype'])
                    : '';
                $isImage = strpos($mimeType, 'image/') === 0;
                ?>
                <article class="fm-item fm-file-item">
                    <?php if ($canManage): ?>
                        <label class="fm-file-selector">
                            <input type="checkbox"
                                   name="files[]"
                                   value="<?php echo $escape($file['id']); ?>"
                                   form="fm-bulk-delete-form"
                                   class="fm-file-checkbox" />
                            <span class="fm-visually-hidden">
                                <?php echo $escape($fmText('select_file_prefix')); ?> <?php echo $escape($file['filename']); ?>
                            </span>
                        </label>
                    <?php endif; ?>
                    <a class="fm-item-main"
                       href="<?php echo $escape($infoUrl); ?>">
                        <?php if ($isImage): ?>
                            <span class="fm-image-preview" aria-hidden="true">
                                <img src="<?php echo $escape($openUrl); ?>"
                                     alt=""
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.parentNode.className='fm-item-icon'; this.parentNode.innerHTML=<?php echo json_encode($fmText('image'), JSON_UNESCAPED_UNICODE); ?>;" />
                            </span>
                        <?php else: ?>
                            <span class="fm-item-icon" aria-hidden="true">
                                <?php echo $fileIcon($file); ?>
                            </span>
                        <?php endif; ?>
                        <span class="fm-item-text">
                            <strong><?php echo $escape($file['filename']); ?></strong>
                            <span>
                                <?php echo $escape(
                                    !empty($file['mimetype'])
                                        ? $file['mimetype']
                                        : (!empty($file['datatype']) ? strtoupper((string) $file['datatype']) : $fmText('file'))
                                ); ?>
                                <?php if (isset($file['filesize'])): ?>
                                    · <?php echo $escape($formatBytes($file['filesize'])); ?>
                                <?php endif; ?>
                            </span>
                        </span>
                    </a>

                    <div class="fm-item-actions">
                        <a href="<?php echo $escape($infoUrl); ?>"><?php echo $escape($fmText('details')); ?></a>
                        <a href="<?php echo $escape($openUrl); ?>"
                           target="_blank"
                           rel="noopener"><?php echo $escape($fmText('open_file')); ?></a>
                        <?php if ($canManage): ?>
                            <form class="fm-delete-form"
                                  method="post"
                                  action="<?php echo $escape($fmUri(array(
                                      'action' => 'multideleteconfirm',
                                  ))); ?>"
                                  onsubmit="return window.confirm(<?php echo json_encode($fmText('confirm_delete_file'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);">
                                <input type="hidden" name="folder"
                                       value="<?php echo $escape($folderId); ?>" />
                                <input type="hidden" name="files[]"
                                       value="<?php echo $escape($file['id']); ?>" />
                                <button type="submit" class="fm-delete-button"><?php echo $escape($fmText('delete')); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($canManage): ?>
    <section class="fm-upload-panel" aria-labelledby="fm-upload-heading">
        <div class="fm-section-heading">
            <div>
                <h2 id="fm-upload-heading"><?php echo $escape($fmText('upload_files')); ?></h2>
                <p><?php echo $escape($fmText('upload_folder_help')); ?></p>
            </div>
        </div>

        <?php if (
            isset($quota['quotausage'], $quota['quota'])
            && $quota['quotausage'] >= $quota['quota']
        ): ?>
            <div class="fm-notice fm-notice-error" role="alert">
                <?php echo $escape($fmText('quota_reached')); ?>
            </div>
        <?php else: ?>
            <!-- NATIVE_DRAG_DROP_UPLOAD -->
            <p id="fm-upload-drop-help" class="fm-upload-drop-help">
                <?php echo $escape($fmText('drop_help')); ?>
            </p>
            <p id="fm-upload-drop-status"
               class="fm-upload-drop-status"
               role="status"
               aria-live="polite"></p>

            <div class="fm-legacy-upload">
                <?php
                echo $this->objUpload->show(
                    $folderId,
                    isset($quota['quota'], $quota['quotausage'])
                        ? ($quota['quota'] - $quota['quotausage'])
                        : null
                );
                ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<script>
(function () {
    'use strict';

    var dropStatus = document.getElementById('fm-upload-drop-status');
    var uploadContainer = document.querySelector('.fm-legacy-upload');
    var dropzone = uploadContainer;
    var uploadForm = uploadContainer ? uploadContainer.querySelector('form') : null;
    var uploadInputs = uploadForm
        ? uploadForm.querySelectorAll('input[type="file"]')
        : [];
    var dragDepth = 0;

    function setDropStatus(message, isError) {
        if (!dropStatus) {
            return;
        }
        dropStatus.textContent = message;
        dropStatus.classList.toggle('fm-upload-drop-error', Boolean(isError));
    }

    function containsFiles(event) {
        var types = event.dataTransfer && event.dataTransfer.types;
        if (!types) {
            return false;
        }
        return Array.prototype.indexOf.call(types, 'Files') !== -1;
    }

    function assignDroppedFiles(files) {
        if (!uploadForm || !uploadInputs.length) {
            setDropStatus(<?php echo json_encode($fmText('upload_form_unavailable'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>, true);
            return;
        }
        if (!files || !files.length) {
            setDropStatus(<?php echo json_encode($fmText('no_dropped_files'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>, true);
            return;
        }
        if (files.length > uploadInputs.length) {
            setDropStatus(
                <?php echo json_encode($fmText('drop_limit_prefix'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?> + ' ' + uploadInputs.length + ' ' + <?php echo json_encode($fmText('drop_limit_suffix'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
                true
            );
            return;
        }
        if (typeof DataTransfer === 'undefined') {
            setDropStatus(
                <?php echo json_encode($fmText('drop_unsupported'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
                true
            );
            return;
        }

        Array.prototype.forEach.call(uploadInputs, function (input, index) {
            var transfer = new DataTransfer();
            if (index < files.length) {
                transfer.items.add(files[index]);
            }
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        var names = Array.prototype.map.call(files, function (file) {
            return file.name;
        });
        // NATIVE_STAGED_DROP_UPLOAD
        setDropStatus(
            names.join(', ') + ' ' + (names.length === 1
                ? <?php echo json_encode($fmText('ready_one'), JSON_UNESCAPED_UNICODE); ?>
                : <?php echo json_encode($fmText('ready_many'), JSON_UNESCAPED_UNICODE); ?>),
            false
        );
    }

    if (dropzone && uploadForm && uploadInputs.length) {
        dropzone.addEventListener('dragenter', function (event) {
            if (!containsFiles(event)) {
                return;
            }
            event.preventDefault();
            dragDepth++;
            dropzone.classList.add('fm-upload-drop-active');
        });
        dropzone.addEventListener('dragover', function (event) {
            if (!containsFiles(event)) {
                return;
            }
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        });
        dropzone.addEventListener('dragleave', function (event) {
            if (!containsFiles(event)) {
                return;
            }
            dragDepth = Math.max(0, dragDepth - 1);
            if (dragDepth === 0) {
                dropzone.classList.remove('fm-upload-drop-active');
            }
        });
        dropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            dragDepth = 0;
            dropzone.classList.remove('fm-upload-drop-active');
            assignDroppedFiles(event.dataTransfer.files);
        });
    }

    var toggles = document.querySelectorAll('[data-fm-toggle]');
    Array.prototype.forEach.call(toggles, function (button) {
        button.addEventListener('click', function () {
            var panel = document.getElementById(button.getAttribute('data-fm-toggle'));
            if (!panel) {
                return;
            }
            panel.hidden = !panel.hidden;
            button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
            if (!panel.hidden) {
                var input = panel.querySelector('input[type="text"]');
                if (input) {
                    input.focus();
                    input.select();
                }
            }
        });
        button.setAttribute('aria-expanded', 'false');
    });

    var bulkForm = document.getElementById('fm-bulk-delete-form');
    var fileCheckboxes = document.querySelectorAll('.fm-file-checkbox');
    var selectAll = document.getElementById('fm-select-all');
    var clearSelection = document.getElementById('fm-clear-selection');
    var deleteSelected = document.getElementById('fm-delete-selected');
    var selectionCount = document.getElementById('fm-selection-count');

    function updateFileSelection() {
        var count = 0;
        Array.prototype.forEach.call(fileCheckboxes, function (checkbox) {
            if (checkbox.checked) {
                count++;
            }
            var card = checkbox.closest ? checkbox.closest('.fm-file-item') : null;
            if (card) {
                card.classList.toggle('fm-item-selected', checkbox.checked);
            }
        });
        if (selectionCount) {
            selectionCount.textContent =
                count + ' ' + (count === 1
                    ? <?php echo json_encode($fmText('one_selected'), JSON_UNESCAPED_UNICODE); ?>
                    : <?php echo json_encode($fmText('many_selected'), JSON_UNESCAPED_UNICODE); ?>);
        }
        if (deleteSelected) {
            deleteSelected.disabled = count === 0;
        }
        return count;
    }

    Array.prototype.forEach.call(fileCheckboxes, function (checkbox) {
        checkbox.addEventListener('change', updateFileSelection);
    });
    if (selectAll) {
        selectAll.addEventListener('click', function () {
            Array.prototype.forEach.call(fileCheckboxes, function (checkbox) {
                checkbox.checked = true;
            });
            updateFileSelection();
        });
    }
    if (clearSelection) {
        clearSelection.addEventListener('click', function () {
            Array.prototype.forEach.call(fileCheckboxes, function (checkbox) {
                checkbox.checked = false;
            });
            updateFileSelection();
        });
    }
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (event) {
            var count = updateFileSelection();
            var template = count === 1
                ? <?php echo json_encode($fmText('bulk_delete_one'), JSON_UNESCAPED_UNICODE); ?>
                : <?php echo json_encode($fmText('bulk_delete_many'), JSON_UNESCAPED_UNICODE); ?>;
            var question = template.replace('[COUNT]', String(count));
            if (count === 0 || !window.confirm(question)) {
                event.preventDefault();
            }
        });
    }
    updateFileSelection();

    var items = document.getElementById('fm-items');
    var viewButtons = document.querySelectorAll('[data-fm-view]');

    Array.prototype.forEach.call(viewButtons, function (button) {
        button.addEventListener('click', function () {
            if (!items) {
                return;
            }
            var view = button.getAttribute('data-fm-view');
            items.className = 'fm-items fm-items-' + view;

            Array.prototype.forEach.call(viewButtons, function (candidate) {
                candidate.setAttribute(
                    'aria-pressed',
                    candidate === button ? 'true' : 'false'
                );
            });

            try {
                window.localStorage.setItem('chisimba-filemanager-view', view);
            } catch (error) {
                // Local storage is optional.
            }
        });
    });

    try {
        var storedView = window.localStorage.getItem('chisimba-filemanager-view');
        if (storedView === 'list') {
            var listButton = document.querySelector('[data-fm-view="list"]');
            if (listButton) {
                listButton.click();
            }
        }
    } catch (error) {
        // Local storage is optional.
    }
}());
</script>

<style>
.fm-breadcrumbs {
    margin: 0 0 1rem;
    font-size: .92rem;
}
.fm-toolbar,
.fm-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
}
.fm-toolbar {
    padding: .75rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(127, 127, 127, .28);
    border-radius: .75rem;
}
.fm-toolbar-primary,
.fm-view-controls,
.fm-inline-form,
.fm-item-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
}
.fm-button,
.fm-icon-button,
.fm-toolbar button,
.fm-action-panel button,
.fm-upload-panel button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid currentColor;
    border-radius: .45rem;
    cursor: pointer;
    text-decoration: none;
}
.fm-button-secondary,
.fm-icon-button {
    background: transparent;
}
.fm-action-panel,
.fm-browser,
.fm-upload-panel {
    margin-bottom: 1rem;
    padding: 1rem;
    border: 1px solid rgba(127, 127, 127, .28);
    border-radius: .75rem;
}
.fm-action-panel h2,
.fm-section-heading h2 {
    margin: 0;
    font-size: 1.2rem;
}
.fm-section-heading p {
    margin: .25rem 0 0;
    opacity: .72;
}
.fm-inline-form {
    margin-top: .5rem;
}
.fm-inline-form input {
    flex: 1 1 15rem;
}
.fm-notice {
    padding: .75rem 1rem;
    margin-bottom: 1rem;
    border-radius: .5rem;
}
.fm-notice-success {
    border: 1px solid #2f7d32;
}
.fm-notice-error {
    border: 1px solid #a32828;
}
.fm-items {
    display: grid;
    gap: .75rem;
    margin-top: 1rem;
}
.fm-items-grid {
    grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr));
}
.fm-items-list {
    grid-template-columns: 1fr;
}
.fm-item {
    display: flex;
    min-width: 0;
    border: 1px solid rgba(127, 127, 127, .28);
    border-radius: .65rem;
    overflow: hidden;
}
.fm-items-grid .fm-item {
    flex-direction: column;
}
.fm-items-list .fm-item {
    align-items: center;
}
.fm-item-main {
    display: flex;
    align-items: center;
    gap: .8rem;
    min-width: 0;
    flex: 1;
    padding: .9rem;
    color: inherit;
    text-decoration: none;
}
.fm-item-main:hover strong,
.fm-item-main:focus strong {
    text-decoration: underline;
}
.fm-item-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 3.5rem;
    min-height: 3.5rem;
    font-size: 1rem;
    font-weight: 700;
    text-align: center;
}
.fm-image-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 5rem;
    width: 5rem;
    height: 4rem;
    overflow: hidden;
    border-radius: .4rem;
    background: rgba(127, 127, 127, .12);
}
.fm-image-preview img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.fm-items-grid .fm-image-preview {
    flex-basis: auto;
    width: 100%;
    height: 9rem;
    border-radius: .5rem;
}
.fm-items-grid .fm-item-main {
    align-items: flex-start;
    flex-direction: column;
}
.fm-item-text {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.fm-item-text strong,
.fm-item-text span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fm-item-text span {
    margin-top: .2rem;
    font-size: .82rem;
    opacity: .7;
}
.fm-item-actions {
    padding: 0 .9rem .8rem;
}
.fm-items-list .fm-item-actions {
    padding: .9rem;
}
.fm-empty {
    padding: 3rem 1rem;
    text-align: center;
}
.fm-empty > span {
    font-size: 3rem;
}
.fm-empty h3 {
    margin-bottom: .25rem;
}
.fm-legacy-upload {
    margin-top: 1rem;
}
.fm-legacy-upload fieldset,
.fm-legacy-upload form {
    max-width: 100%;
}
@media (max-width: 640px) {
    .fm-toolbar,
    .fm-section-heading {
        align-items: stretch;
        flex-direction: column;
    }
    .fm-view-controls {
        align-self: flex-start;
    }
    .fm-items-grid {
        grid-template-columns: 1fr;
    }
}

/* Accessible bulk file selection; main browser typography is unchanged. */
.fm-bulk-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    margin: 0 0 .85rem;
    font-size: .86rem;
}
.fm-bulk-actions button {
    min-height: 2.1rem;
    padding: .3rem .65rem;
}
.fm-danger-button {
    margin-left: auto;
    border: 1px solid #a32121;
    border-radius: .35rem;
    background: #a32121;
    color: #fff;
}
.fm-danger-button:disabled {
    opacity: .45;
    cursor: not-allowed;
}
.fm-file-item { position: relative; }
.fm-file-selector {
    position: absolute;
    z-index: 2;
    top: .55rem;
    right: .55rem;
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    border-radius: .35rem;
    background: rgba(255, 255, 255, .94);
    box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
}
.fm-file-checkbox {
    width: 1.1rem;
    height: 1.1rem;
    margin: 0;
}
.fm-item-selected {
    outline: 3px solid #0878be;
    outline-offset: 1px;
}
.fm-visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}
@media (max-width: 640px) {
    .fm-danger-button { margin-left: 0; }
}


/* NATIVE_TWO_LINE_FILENAMES
 * Metadata remains compact; filenames get two readable lines.
 */
.fm-item-text strong {
    display: -webkit-box;
    min-width: 0;
    overflow: hidden;
    overflow-wrap: anywhere;
    word-break: normal;
    white-space: normal;
    text-overflow: clip;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    line-height: 1.3;
    max-height: 2.6em;
    font-size: .95rem;
}
.fm-item-text span {
    white-space: nowrap;
    text-overflow: ellipsis;
}


/* Native progressive enhancement over the proven legacy upload service. */
.fm-upload-drop-help {
    margin: .75rem 0 .4rem;
    font-size: .88rem;
}
.fm-legacy-upload {
    padding: .85rem;
    border: 2px dashed #52758a;
    border-radius: .65rem;
    background: rgba(82, 117, 138, .06);
    transition: border-color .15s ease, background-color .15s ease;
}
.fm-legacy-upload.fm-upload-drop-active {
    border-color: #0878be;
    background: rgba(8, 120, 190, .12);
}
.fm-upload-drop-status {
    min-height: 1.4em;
    margin: .5rem 0 0;
    font-size: .88rem;
}
.fm-upload-drop-error {
    color: #a32121;
    font-weight: 600;
}

</style>

<style id="fm-native-filetype-icon-styles">
.fm-filetype-svg {
    display: block;
    width: 3.25rem;
    height: 3.25rem;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.6;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.fm-item-icon,
.fm-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #315b72;
}
.fm-filetype-folder { color: #a06012; }
.fm-filetype-pdf { color: #b42318; }
.fm-filetype-audio { color: #6b3fa0; }
.fm-filetype-video { color: #075985; }
.fm-filetype-archive { color: #7a4b16; }
.fm-filetype-spreadsheet { color: #207245; }
.fm-filetype-presentation { color: #c43e1c; }
.fm-filetype-code { color: #475569; }
.fm-image-preview + .fm-item-text,
.fm-item-icon + .fm-item-text { min-width: 0; }
</style>

<style id="fm-native-delete-styles">
.fm-delete-form { display: inline; margin: 0; }
.fm-delete-button {
    appearance: none;
    padding: 0;
    border: 0;
    background: transparent;
    color: #9f2d20;
    font: inherit;
    text-decoration: underline;
    cursor: pointer;
}
.fm-delete-button:hover { color: #721c14; }
.fm-delete-button:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 3px;
}
.fm-folder-item > .fm-delete-form {
    display: block;
    padding: 0 1rem .85rem;
}
</style>
