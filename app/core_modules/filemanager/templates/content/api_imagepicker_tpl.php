<?php

/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_START */
$fmText = function ($code) {
    return $this->objLanguage->languageText('mod_filemanager_native_' . $code, 'filemanager');
};
/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_END */
if (!$GLOBALS['kewl_entry_point_run']) {
    die('You cannot view this page directly');
}

$apiUrl = $this->uri(
    array('action' => 'apiimages'),
    'filemanager',
    '',
    false,
    false,
    true
);
$uploadUrl = $this->uri(
    array('action' => 'apiuploadimage'),
    'filemanager',
    '',
    false,
    false,
    true
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($fmText('select_image'), ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            font-family: system-ui, sans-serif;
            color-scheme: light dark;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 1rem;
        }
        header {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        h1 {
            margin: 0;
            font-size: 1.25rem;
        }
        button,
        input {
            font: inherit;
        }
        button {
            padding: .55rem .8rem;
            cursor: pointer;
        }
        .upload-form {
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
            margin: 0;
        }
        #status {
            min-height: 1.5rem;
            margin: .5rem 0 1rem;
        }
        .folders,
        .images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: .75rem;
        }
        .folder,
        .image {
            width: 100%;
            min-height: 110px;
            border: 1px solid currentColor;
            border-radius: .5rem;
            background: transparent;
            text-align: center;
        }
        .folder {
            display: grid;
            place-items: center;
            font-weight: 600;
        }
        .image {
            padding: .5rem;
        }
        .image img {
            display: block;
            width: 100%;
            height: 110px;
            object-fit: contain;
            margin-bottom: .4rem;
        }
        .image span {
            display: block;
            overflow-wrap: anywhere;
        }
        section + section {
            margin-top: 1.5rem;
        }
        .empty {
            opacity: .75;
        }
    </style>
</head>
<body>
<header>
    <button id="up" type="button" hidden aria-label="<?php echo htmlspecialchars($fmText('parent_folder'), ENT_QUOTES, 'UTF-8'); ?>">← Up</button>
    <h1><?php echo htmlspecialchars($fmText('select_image'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <form id="upload-form" class="upload-form" enctype="multipart/form-data">
        <label for="image-upload"><?php echo htmlspecialchars($fmText('upload_image'), ENT_QUOTES, 'UTF-8'); ?></label>
        <input id="image-upload" name="image" type="file"
            accept="image/jpeg,image/png,image/gif,image/webp,image/bmp" required>
        <button id="upload-button" type="submit"><?php echo htmlspecialchars($fmText('upload'), ENT_QUOTES, 'UTF-8'); ?></button>
    </form>
</header>

<div id="status" role="status" aria-live="polite"><?php echo htmlspecialchars($fmText('loading_images'), ENT_QUOTES, 'UTF-8'); ?></div>

<section aria-labelledby="folders-heading">
    <h2 id="folders-heading"><?php echo htmlspecialchars($fmText('folders'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div id="folders" class="folders"></div>
</section>

<section aria-labelledby="images-heading">
    <h2 id="images-heading"><?php echo htmlspecialchars($fmText('images'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <div id="images" class="images"></div>
</section>

<script>
(function () {
    'use strict';

    var apiUrl = <?php echo json_encode(html_entity_decode($apiUrl, ENT_QUOTES, 'UTF-8')); ?>;
    var uploadUrl = <?php echo json_encode(html_entity_decode($uploadUrl, ENT_QUOTES, 'UTF-8')); ?>;
    var status = document.getElementById('status');
    var folders = document.getElementById('folders');
    var images = document.getElementById('images');
    var up = document.getElementById('up');
    var uploadForm = document.getElementById('upload-form');
    var uploadInput = document.getElementById('image-upload');
    var uploadButton = document.getElementById('upload-button');
    var currentFolderId = '';

    function clear(node) {
        while (node.firstChild) {
            node.removeChild(node.firstChild);
        }
    }

    function emptyMessage(node, text) {
        var p = document.createElement('p');
        p.className = 'empty';
        p.textContent = text;
        node.appendChild(p);
    }

    function selectImage(file) {
        var opener = window.opener;
        if (opener && opener.ChisimbaEditor &&
                typeof opener.ChisimbaEditor.selectFile === 'function') {
            opener.ChisimbaEditor.selectFile(file.url, file.width, file.height);
            window.close();
            return;
        }

        status.textContent = <?php echo json_encode($fmText('editor_receive_failed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    }

    function render(data) {
        clear(folders);
        clear(images);

        currentFolderId = data.folder.id;
        status.textContent = data.folder.path;
        up.hidden = !data.folder.parentId;
        up.dataset.folderId = data.folder.parentId || '';

        if (!data.folders.length) {
            emptyMessage(folders, <?php echo json_encode($fmText('no_subfolders'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);
        } else {
            data.folders.forEach(function (folder) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'folder';
                button.textContent = '📁 ' + folder.name;
                button.addEventListener('click', function () {
                    load(folder.id);
                });
                folders.appendChild(button);
            });
        }

        if (!data.files.length) {
            emptyMessage(images, <?php echo json_encode($fmText('no_images'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);
        } else {
            data.files.forEach(function (file) {
                var button = document.createElement('button');
                var image = document.createElement('img');
                var label = document.createElement('span');

                button.type = 'button';
                button.className = 'image';
                button.setAttribute('aria-label', <?php echo json_encode($fmText('select_item'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?> + ' ' + file.name);

                image.src = file.url;
                image.alt = '';
                image.loading = 'lazy';

                label.textContent = file.name;

                button.appendChild(image);
                button.appendChild(label);
                button.addEventListener('click', function () {
                    selectImage(file);
                });

                images.appendChild(button);
            });
        }
    }

    function load(folderId) {
        var url = apiUrl;
        if (folderId) {
            url += (url.indexOf('?') === -1 ? '?' : '&') +
                'folder=' + encodeURIComponent(folderId);
        }

        status.textContent = 'Loading images…';

        fetch(url, {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'}
        })
        .then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data.ok) {
                    throw new Error(
                        data.error && data.error.message
                            ? data.error.message
                            : <?php echo json_encode($fmText('load_images_failed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
                    );
                }
                return data;
            });
        })
        .then(render)
        .catch(function (error) {
            clear(folders);
            clear(images);
            status.textContent = error.message;
        });
    }

    up.addEventListener('click', function () {
        load(up.dataset.folderId);
    });

    function recoverUploadedImage(filename) {
        var url = apiUrl;
        if (currentFolderId) {
            url += (url.indexOf('?') === -1 ? '?' : '&') +
                'folder=' + encodeURIComponent(currentFolderId);
        }

        return fetch(url, {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'}
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data || !data.ok || !Array.isArray(data.files)) {
                throw new Error(<?php echo json_encode($fmText('picker_reload_failed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);
            }

            var dot = filename.lastIndexOf('.');
            var stem = dot === -1 ? filename : filename.slice(0, dot);
            var extension = dot === -1 ? '' : filename.slice(dot);
            var candidates = data.files.filter(function (file) {
                if (!file || typeof file.name !== 'string') {
                    return false;
                }
                return file.name === filename ||
                    (file.name.indexOf(stem + '_') === 0 &&
                    (extension === '' || file.name.slice(-extension.length) === extension));
            });

            candidates.sort(function (left, right) {
                return String(right.name).localeCompare(
                    String(left.name), undefined,
                    {numeric: true, sensitivity: 'base'}
                );
            });

            if (!candidates.length) {
                render(data);
                throw new Error(<?php echo json_encode($fmText('select_refreshed'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);
            }

            selectImage(candidates[0]);
            return candidates[0];
        });
    }

    uploadForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!uploadInput.files || !uploadInput.files.length) {
            status.textContent = <?php echo json_encode($fmText('choose_image'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            return;
        }

        var formData = new FormData();
        formData.append('image', uploadInput.files[0]);
        var url = uploadUrl;
        if (currentFolderId) {
            url += (url.indexOf('?') === -1 ? '?' : '&') +
                'folder=' + encodeURIComponent(currentFolderId);
        }

        uploadButton.disabled = true;
        uploadInput.disabled = true;
        status.textContent = 'Uploading image…';

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'},
            body: formData
        })
        .then(function (response) {
            return response.text().then(function (body) {
                var data;
                if (!body.trim()) {
                    throw new Error(
                        <?php echo json_encode($fmText('empty_response'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
                    );
                }
                try {
                    data = JSON.parse(body);
                } catch (error) {
                    throw new Error(
                        <?php echo json_encode($fmText('invalid_response'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?> + ' ' +
                        '(HTTP ' + response.status + ').'
                    );
                }
                if (!response.ok || !data.ok) {
                    throw new Error(data.error && data.error.message
                        ? data.error.message : <?php echo json_encode($fmText('unable_upload_image'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);
                }
                return data;
            });
        })
        .then(function (data) {
            uploadForm.reset();
            status.textContent = data.file.name + ' uploaded.';
            selectImage(data.file);
        })
        .catch(function (error) {
            // The legacy upload service can save the file while returning a
            // non-JSON response. Recover from storage instead of reporting a
            // false transport failure.
            return recoverUploadedImage(uploadInput.files[0].name)
                .catch(function (recoveryError) {
                    status.textContent = recoveryError.message || error.message;
                });
        })
        .then(function () {
            uploadButton.disabled = false;
            uploadInput.disabled = false;
        });
    });

    load(null);
}());
</script>
</body>
</html>
