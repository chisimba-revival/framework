<?php
/**
 * Safe renderer for the curated Chisimba Lucide icon catalogue.
 *
 * Modules choose an allowlisted semantic icon. The skin owns presentation.
 * SVGs render inline and inherit currentColor without JavaScript or a CDN.
 *
 * @author Derek Keats
 */
class iconservice extends ChisimbaObject
{
    private $allowedIcons = array(
        'file-text' => true,
        'external-link' => true,
        'file-archive' => true,
        'file-down' => true,
        'image-plus' => true,
        'smartphone' => true,
        'video' => true,
 'calendar' => true, 'chevron-left' => true, 'chevron-right' => true,
        'chevron-up' => true, 'chevron-down' => true,
        'circle-alert' => true, 'circle-check' => true, 'clock' => true,
        'download' => true, 'eye' => true, 'info' => true, 'minus' => true,
        'pencil' => true, 'pin' => true, 'plus' => true, 'search' => true,
        'trash-2' => true, 'triangle-alert' => true, 'upload' => true,
        'x' => true, 'user' => true, 'key-round' => true,
        'log-in' => true, 'log-out' => true,
        'book-open' => true, 'folder-open' => true, 'library' => true, 'scroll-text' => true, 'user-cog' => true, 'users-round' => true,
        'shield-check' => true, 'boxes' => true, 'languages' => true,
        'puzzle' => true, 'text' => true, 'server-cog' => true,
        'code-2' => true,
        'square-pen' => true, 'case-upper' => true, 'clipboard-pen' => true,
    );

    /**
     * @param string $name
     * @param array $options label, decorative and class are supported.
     * @return string
     */
    public function render($name, $options = array())
    {
        if (!is_string($name) || !isset($this->allowedIcons[$name])) {
            throw new InvalidArgumentException('icon_name_unknown');
        }
        if (!is_array($options)) {
            throw new InvalidArgumentException('icon_options_invalid');
        }
        $label = isset($options['label']) ? trim((string) $options['label']) : '';
        $decorative = isset($options['decorative'])
            ? (bool) $options['decorative'] : ($label === '');
        if ($decorative && $label !== '') {
            throw new InvalidArgumentException('icon_decorative_label_invalid');
        }
        $extraClass = isset($options['class']) ? trim((string) $options['class']) : '';
        if ($extraClass !== '' && !preg_match('/^[A-Za-z0-9_-]+(?:\\s+[A-Za-z0-9_-]+)*$/', $extraClass)) {
            throw new InvalidArgumentException('icon_class_invalid');
        }

        $path = dirname(__DIR__) . '/resources/icons/lucide/' . $name . '.svg';
        $svg = @file_get_contents($path);
        if ($svg === false) {
            throw new RuntimeException('icon_asset_missing');
        }
        $classes = 'chisimba-icon chisimba-icon--' . $name;
        if ($extraClass !== '') {
            $classes .= ' ' . $extraClass;
        }
        $svg = preg_replace('/\\swidth="[^"]*"/', '', $svg, 1);
        $svg = preg_replace('/\\sheight="[^"]*"/', '', $svg, 1);
        $svg = preg_replace('/stroke-width="[^"]*"/', 'stroke-width="1.5"', $svg, 1);
        $attrs = ' class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '"';
        $attrs .= ' width="1em" height="1em" focusable="false"';
        if ($decorative) {
            $attrs .= ' aria-hidden="true"';
        } else {
            $escaped = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $attrs .= ' role="img" aria-label="' . $escaped . '"';
            $svg = preg_replace('/(<svg\\b[^>]*>)/', '$1<title>' . $escaped . '</title>', $svg, 1);
        }
        return preg_replace('/<svg\\b/', '<svg' . $attrs, $svg, 1);
    }
}
