<?php

/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_START */
$fmText = function ($code) {
    return $this->objLanguage->languageText('mod_filemanager_native_' . $code, 'filemanager');
};
/* NATIVE_FILEMANAGER_LANGUAGE_HELPER_END */
/**
 * Native file-manager layout.
 *
 * Deliberately thin: the skin remains responsible for the site shell while
 * this layout supplies a semantic file-manager workspace.
 */
?>
<div class="fm-native-shell">
    <header class="fm-native-header">
        <div>
            <p class="fm-native-eyebrow">
                <?php echo htmlspecialchars(
                    $this->objLanguage->languageText(
                        'mod_filemanager_name',
                        'filemanager'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </p>
            <h1 class="fm-native-title">
                <?php echo htmlspecialchars(
                    isset($folderpath) && $folderpath !== ''
                        ? $folderpath
                        : $this->objLanguage->languageText(
                            'mod_filemanager_name',
                            'filemanager'
                        ),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </h1>
        </div>

        <form class="fm-native-search"
              method="get"
              action="<?php echo htmlspecialchars($this->uri(array()), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="module" value="filemanager" />
            <input type="hidden" name="action" value="search" />
            <label class="fm-visually-hidden" for="fm-native-search-input">
                <?php echo htmlspecialchars(
                    $this->objLanguage->languageText('word_search', 'system', 'Search'),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </label>
            <input id="fm-native-search-input"
                   name="filequery"
                   type="search"
                   value="<?php echo htmlspecialchars(
                       (string) $this->getParam('filequery'),
                       ENT_QUOTES,
                       'UTF-8'
                   ); ?>"
                   placeholder="<?php echo htmlspecialchars($fmText('search_files'), ENT_QUOTES, 'UTF-8'); ?>" />
            <button type="submit"><?php echo htmlspecialchars($fmText('search'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
    </header>

    <main class="fm-native-main">
        <?php echo $this->getContent(); ?>
    </main>
</div>

<style>
.fm-native-shell {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}
.fm-native-header {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.fm-native-eyebrow {
    margin: 0 0 .2rem;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .7;
}
.fm-native-title {
    margin: 0;
    font-size: clamp(1.5rem, 3vw, 2.25rem);
}
.fm-native-search {
    display: flex;
    gap: .5rem;
    min-width: min(100%, 22rem);
}
.fm-native-search input {
    min-width: 0;
    flex: 1;
}
.fm-native-search input,
.fm-native-search button,
.fm-native-shell input,
.fm-native-shell select,
.fm-native-shell button {
    min-height: 2.6rem;
    padding: .55rem .75rem;
    font: inherit;
}
.fm-native-main {
    min-width: 0;
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
@media (max-width: 760px) {
    .fm-native-header {
        align-items: stretch;
        flex-direction: column;
    }
    .fm-native-search {
        width: 100%;
    }
}
</style>
