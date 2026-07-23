<?php

/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_START */
$fmText = function ($code) {
    return $this->objLanguage->languageText('mod_filemanager_native_' . $code, 'filemanager');
};
/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_END */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$fmUri = function ($params = array()) {
    return htmlspecialchars_decode(
        $this->uri($params, 'filemanager'),
        ENT_QUOTES
    );
};

$formatBytes = static function ($bytes) {
    $value = max(0, (float) $bytes);
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $index = 0;
    while ($value >= 1024 && $index < count($units) - 1) {
        $value /= 1024;
        $index++;
    }
    return ($index === 0 ? (string) (int) $value : number_format($value, 1))
        . ' ' . $units[$index];
};

$mime = isset($file['mimetype']) ? strtolower((string) $file['mimetype']) : '';
$type = isset($file['datatype']) ? strtolower((string) $file['datatype']) : '';
$category = isset($file['category']) ? strtolower((string) $file['category']) : '';

$typeLabel = $fmText('file');
if (strpos($mime, 'image/') === 0 || $category === 'images') {
    $typeLabel = $fmText('image');
} elseif (strpos($mime, 'audio/') === 0) {
    $typeLabel = $fmText('audio');
} elseif (strpos($mime, 'video/') === 0) {
    $typeLabel = $fmText('video');
} elseif ($type === 'pdf' || $mime === 'application/pdf') {
    $typeLabel = 'PDF';
} elseif (in_array($type, array('zip', 'tar', 'gz', 'rar', '7z'), true)) {
    $typeLabel = $fmText('archive');
}

$fileParams = array(
    'action' => 'file',
    'id' => $file['id'],
    'filename' => $file['filename'],
);
if ($type !== '') {
    $fileParams['type'] = '.' . ltrim($type, '.');
}
$openUrl = $fmUri($fileParams);
$backUrl = $fmUri(array('action' => 'viewfolder', 'folder' => $folderId));
$editUrl = $fmUri(array('action' => 'editfiledetails', 'id' => $file['id']));
$embedText = '[FILEPREVIEW id="' . $file['id'] . '" comment="'
    . $file['filename'] . '" /]';

$description = '';
if (isset($file['filedescription'])) {
    $description = (string) $file['filedescription'];
} elseif (isset($file['description'])) {
    $description = (string) $file['description'];
}

// NATIVE_IMAGE_ALT_TEXT_DETAILS
$isImage = ($typeLabel === 'Image');
$message = $this->getParam('message');
?>

<article class="fm-details">
    <nav class="fm-details-breadcrumbs" aria-label="<?php echo $escape($fmText('file_location')); ?>">
        <?php echo isset($fileBreadrumbs) ? $fileBreadrumbs : ''; ?>
    </nav>

    <?php if ($message === 'filedetailsupdated'): ?>
        <div class="fm-details-notice" role="status">
            File details were updated.
        </div>
    <?php endif; ?>

    <header class="fm-details-header">
        <div>
            <span class="fm-type-badge"><?php echo $escape($typeLabel); ?></span>
            <h1><?php echo $escape(str_replace('_', ' ', $file['filename'])); ?></h1>
            <?php if ($isImage): ?>
                <?php if ($description !== ''): ?>
                    <p class="fm-details-description">
                        <strong><?php echo $escape($fmText('alternative_text')); ?></strong>
                        <?php echo nl2br($escape($description)); ?>
                    </p>
                <?php else: ?>
                    <p class="fm-details-description fm-alt-warning">
                        <strong><?php echo $escape($fmText('alt_empty_warning')); ?></strong>
                        This is correct only when the image is decorative.
                    </p>
                <?php endif; ?>
            <?php elseif ($description !== ''): ?>
                <p class="fm-details-description"><?php echo nl2br($escape($description)); ?></p>
            <?php endif; ?>
        </div>
        <div class="fm-details-actions">
            <a class="fm-button fm-button-secondary"
               href="<?php echo $escape($backUrl); ?>"><?php echo $escape($fmText('back_to_folder')); ?></a>
            <a class="fm-button fm-button-primary"
               href="<?php echo $escape($openUrl); ?>"
               target="_blank"
               rel="noopener"><?php echo $escape($fmText('open_file')); ?></a>
            <?php if (!empty($folderPermission)): ?>
                <a class="fm-button fm-button-secondary"
                   href="<?php echo $escape($editUrl); ?>"><?php echo $escape($fmText('edit_details')); ?></a>
                <form class="fm-details-delete-form"
                      method="post"
                      action="<?php echo $escape($fmUri(array(
                          'action' => 'multideleteconfirm',
                      ))); ?>"
                      onsubmit="return window.confirm(<?php echo json_encode($fmText('confirm_delete_file'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);">
                    <input type="hidden" name="folder"
                           value="<?php echo $escape($folderId); ?>" />
                    <input type="hidden" name="files[]"
                           value="<?php echo $escape($file['id']); ?>" />
                    <button type="submit"
                            class="fm-button fm-button-danger"><?php echo $escape($fmText('delete_file')); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="fm-details-grid">
        <main>
            <section class="fm-details-panel" aria-labelledby="fm-preview-heading">
                <div class="fm-panel-heading">
                    <h2 id="fm-preview-heading"><?php echo $escape($fmText('preview')); ?></h2>
                </div>
                <div class="fm-native-preview">
                    <?php if ($typeLabel === 'Image'): ?>
                        <img src="<?php echo $escape($openUrl); ?>"
                             alt="<?php echo $escape($description); ?>" />
                    <?php elseif ($typeLabel === 'Audio'): ?>
                        <audio controls preload="metadata">
                            <source src="<?php echo $escape($openUrl); ?>"
                                    type="<?php echo $escape($mime); ?>" />
                        </audio>
                    <?php elseif ($typeLabel === 'Video'): ?>
                        <video controls preload="metadata">
                            <source src="<?php echo $escape($openUrl); ?>"
                                    type="<?php echo $escape($mime); ?>" />
                        </video>
                    <?php elseif ($typeLabel === 'PDF'): ?>
                        <object class="fm-pdf-preview"
                                data="<?php echo $escape($openUrl); ?>"
                                type="application/pdf"
                                width="100%"
                                height="720">
                            <p>Preview unavailable.
                                <a href="<?php echo $escape($openUrl); ?>"><?php echo $escape($fmText('open_pdf')); ?></a>.
                            </p>
                        </object>
                    <?php else: ?>
                        <div class="fm-preview-placeholder">
                            <span class="fm-type-badge"><?php echo $escape($typeLabel); ?></span>
                            <p><?php echo $escape($fmText('preview_unavailable')); ?></p>
                            <a href="<?php echo $escape($openUrl); ?>"
                               target="_blank"
                               rel="noopener"><?php echo $escape($fmText('open_file')); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="fm-details-panel" aria-labelledby="fm-information-heading">
                <div class="fm-panel-heading">
                    <h2 id="fm-information-heading"><?php echo $escape($fmText('file_information')); ?></h2>
                </div>
                <dl class="fm-metadata">
                    <div>
                        <dt><?php echo $escape($fmText('filename')); ?></dt>
                        <dd><?php echo $escape($file['filename']); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo $escape($fmText('type')); ?></dt>
                        <dd><?php echo $escape($mime !== '' ? $mime : strtoupper($type)); ?></dd>
                    </div>
                    <?php if (isset($file['filesize'])): ?>
                        <div>
                            <dt><?php echo $escape($fmText('size')); ?></dt>
                            <dd><?php echo $escape($formatBytes($file['filesize'])); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($file['width'], $file['height'])): ?>
                        <div>
                            <dt><?php echo $escape($fmText('dimensions')); ?></dt>
                            <dd><?php echo $escape($file['width']); ?> × <?php echo $escape($file['height']); ?> px</dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($isImage): ?>
                        <div>
                            <dt><?php echo $escape($fmText('alternative_text')); ?></dt>
                            <dd>
                                <?php if ($description !== ''): ?>
                                    <?php echo nl2br($escape($description)); ?>
                                <?php else: ?>
                                    <em><?php echo $escape($fmText('empty_decorative')); ?></em>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($file['license'])): ?>
                        <div>
                            <dt><?php echo $escape($fmText('license')); ?></dt>
                            <dd><?php echo $escape($file['license']); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($file['access'])): ?>
                        <div>
                            <dt><?php echo $escape($fmText('access')); ?></dt>
                            <dd><?php echo $escape(str_replace('_', ' ', $file['access'])); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($file['visibility'])): ?>
                        <div>
                            <dt><?php echo $escape($fmText('visibility')); ?></dt>
                            <dd><?php echo $escape($file['visibility']); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <div class="fm-tag-list">
                    <strong><?php echo $escape($fmText('tags')); ?></strong>
                    <?php if (empty($tags)): ?>
                        <span><?php echo $escape($fmText('no_tags')); ?></span>
                    <?php else: ?>
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo $escape($fmUri(array(
                                'action' => 'viewbytag',
                                'tag' => $tag,
                            ))); ?>"><?php echo $escape($tag); ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="fm-details-panel" aria-labelledby="fm-embed-heading">
                <div class="fm-panel-heading">
                    <h2 id="fm-embed-heading"><?php echo $escape($fmText('embed_heading')); ?></h2>
                </div>
                <p><?php echo $escape($fmText('embed_help')); ?></p>
                <div class="fm-copy-row">
                    <input id="fm-embed-code"
                           type="text"
                           readonly
                           value="<?php echo $escape($embedText); ?>" />
                    <button type="button" id="fm-copy-embed"><?php echo $escape($fmText('copy')); ?></button>
                </div>
                <p id="fm-copy-status" class="fm-copy-status" role="status"></p>
            </section>

            <?php if ($typeLabel === 'Archive' && $type === 'zip' && !empty($folderPermission)): ?>
                <section class="fm-details-panel" aria-labelledby="fm-archive-heading">
                    <div class="fm-panel-heading">
                        <h2 id="fm-archive-heading"><?php echo $escape($fmText('extract_archive')); ?></h2>
                    </div>
                    <form method="post"
                          action="<?php echo $escape($fmUri(array(
                              'action' => 'extractarchive',
                              'id' => $file['id'],
                          ))); ?>">
                        <?php
        $archiveDestinations = $this->objFolders->getSubFolders($folderId);
        if (!is_array($archiveDestinations)) {
            $archiveDestinations = array();
        }
        ?>
        <label for="input_parentfolder"><?php echo $escape($fmText('extract_to')); ?></label>
        <select id="input_parentfolder" name="parentfolder">
            <option value="<?php echo $escape($folderId); ?>" selected="selected"><?php echo $escape($fmText('current_folder')); ?></option>
            <?php foreach ($archiveDestinations as $archiveDestination): ?>
                <?php
                if (empty($archiveDestination['id']) ||
                        empty($archiveDestination['folderpath'])) {
                    continue;
                }
                $archiveDestinationName = basename(
                    rtrim($archiveDestination['folderpath'], '/')
                );
                ?>
                <option value="<?php echo $escape($archiveDestination['id']); ?>"><?php
                    echo $escape($archiveDestinationName);
                ?></option>
            <?php endforeach; ?>
        </select>
        <p class="fm-help"><?php echo $escape($fmText('no_overwrite')); ?></p>
                        <input type="hidden" name="file" value="<?php echo $escape($file['id']); ?>" />
                        <button type="submit"><?php echo $escape($fmText('extract_files')); ?></button>
                    </form>
                </section>
            <?php endif; ?>
        </main>

        <?php if (!empty($folderPermission)): ?>
            <aside class="fm-details-panel fm-access-panel" aria-labelledby="fm-access-heading">
                <div class="fm-panel-heading">
                    <h2 id="fm-access-heading"><?php echo $escape($fmText('access_visibility')); ?></h2>
                </div>
                <?php
                $fileAccess = $this->getObject('folderaccess', 'filemanager');
                echo $fileAccess->createFileAccessControlForm($file['id']);
                echo $fileAccess->createFileVisibilityForm($file['id']);
                ?>
            </aside>
        <?php endif; ?>
    </div>
</article>

<script>
(function () {
    'use strict';
    var button = document.getElementById('fm-copy-embed');
    var input = document.getElementById('fm-embed-code');
    var status = document.getElementById('fm-copy-status');
    if (!button || !input || !status) {
        return;
    }
    button.addEventListener('click', function () {
        var copied = false;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(function () {
                status.textContent = <?php echo json_encode($fmText('copied'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            }, function () {
                input.select();
                status.textContent = <?php echo json_encode($fmText('copy_keyboard'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            });
            return;
        }
        input.select();
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }
        status.textContent = copied ? <?php echo json_encode($fmText('copied'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?> : <?php echo json_encode($fmText('copy_keyboard'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    });
}());
</script>

<style>
.fm-details { width: 100%; }
.fm-details-breadcrumbs { margin-bottom: 1rem; }
.fm-details-notice {
    margin-bottom: 1rem;
    padding: .8rem 1rem;
    border-left: .3rem solid #2e7d32;
    background: rgba(46, 125, 50, .1);
}
.fm-details-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.fm-details-header h1 {
    margin: .45rem 0 .25rem;
    overflow-wrap: anywhere;
}
.fm-details-description { margin: 0; max-width: 70ch; }
.fm-type-badge {
    display: inline-flex;
    align-items: center;
    min-height: 1.8rem;
    padding: .15rem .55rem;
    border-radius: 999px;
    background: rgba(0, 92, 153, .12);
    color: #034f84;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.fm-details-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.fm-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: .45rem .8rem;
    border: 1px solid currentColor;
    border-radius: .4rem;
    text-decoration: none;
}
.fm-button-primary { background: #075985; color: #fff; }
.fm-button-secondary { background: transparent; color: #075985; }
.fm-details-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(16rem, 1fr);
    gap: 1rem;
}
.fm-details-grid main {
    display: grid;
    min-width: 0;
    gap: 1rem;
}
.fm-details-panel {
    min-width: 0;
    padding: 1rem;
    border: 1px solid rgba(127, 127, 127, .28);
    border-radius: .65rem;
    background: rgba(255, 255, 255, .58);
}
.fm-panel-heading h2 { margin-top: 0; }
.fm-native-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 12rem;
    overflow: hidden;
    border-radius: .45rem;
    background: rgba(127, 127, 127, .08);
}
.fm-native-preview img,
.fm-native-preview video {
    display: block;
    max-width: 100%;
    max-height: 65vh;
}
.fm-native-preview audio { width: min(100%, 38rem); }
.fm-native-preview object { width: 100%; min-height: 65vh; }
.fm-preview-placeholder { padding: 2rem; text-align: center; }
.fm-metadata { margin: 0; }
.fm-metadata > div {
    display: grid;
    grid-template-columns: minmax(7rem, 1fr) 2fr;
    gap: .75rem;
    padding: .55rem 0;
    border-bottom: 1px solid rgba(127, 127, 127, .18);
}
.fm-metadata dt { font-weight: 700; }
.fm-metadata dd { margin: 0; overflow-wrap: anywhere; }
.fm-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: 1rem;
}
.fm-tag-list a,
.fm-tag-list span {
    padding: .15rem .5rem;
    border-radius: 999px;
    background: rgba(127, 127, 127, .12);
}
.fm-copy-row { display: flex; gap: .5rem; }
.fm-copy-row input { min-width: 0; flex: 1; }
.fm-copy-status { min-height: 1.4em; }
.fm-access-panel fieldset { margin-bottom: 1rem; }
.fm-access-panel form { max-width: 100%; }
@media (max-width: 800px) {
    .fm-details-header { flex-direction: column; }
    .fm-details-grid { grid-template-columns: 1fr; }
    .fm-copy-row { align-items: stretch; flex-direction: column; }
}

/* NATIVE_COMPACT_DETAILS_TYPE_V3 */
.fm-details-header h1 {
    font-size: clamp(1.65rem, 2.6vw, 2.2rem);
    line-height: 1.12;
    max-width: 30ch;
}
.fm-details-panel h2 {
    margin-top: 0;
    font-size: 1.3rem;
    line-height: 1.25;
}
.fm-details-panel h3 { font-size: 1.05rem; }
.fm-details-description,
.fm-metadata,
.fm-tag-list,
.fm-details-panel p,
.fm-access-panel,
.fm-access-panel label {
    font-size: .92rem;
    line-height: 1.45;
}
.fm-access-panel fieldset {
    margin: .75rem 0;
    padding: .75rem;
}
.fm-access-panel legend {
    padding: 0 .25rem;
    font-size: .9rem;
}
.fm-access-panel input[type="radio"] + label,
.fm-access-panel label strong {
    font-size: .92rem;
}
.fm-details .fm-button { font-size: .9rem; }

</style>

<style id="fm-native-pdf-preview-styles">
.fm-native-preview .fm-pdf-preview {
    display: block;
    width: 100%;
    min-height: 70vh;
    border: 1px solid rgba(15, 23, 42, .2);
    border-radius: .5rem;
    background: #f8fafc;
}
.fm-native-preview audio { width: min(100%, 46rem); }
.fm-native-preview video {
    display: block;
    width: min(100%, 64rem);
    max-height: 72vh;
    background: #0f172a;
}
@media (max-width: 48rem) {
    .fm-native-preview .fm-pdf-preview {
        min-height: 34rem;
        height: 65vh;
    }
}
</style>

<style id="fm-native-details-delete-styles">
.fm-details-delete-form { display: inline-flex; margin: 0; }
.fm-button-danger {
    border-color: #9f2d20;
    background: transparent;
    color: #9f2d20;
}
.fm-button-danger:hover { background: rgba(159, 45, 32, .08); }
</style>
