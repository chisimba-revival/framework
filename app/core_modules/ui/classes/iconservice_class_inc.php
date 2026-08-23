<?php
/**
 * Safe renderer for the complete, pinned Chisimba Lucide icon catalogue.
 *
 * Modules choose a locally bundled semantic icon. The skin owns presentation.
 * SVGs render inline and inherit currentColor without JavaScript or a CDN.
 *
 * @author Derek Keats
 */
class iconservice extends ChisimbaObject
{
    /**
     * @param string $name
     * @param array $options label, decorative and class are supported.
     * @return string
     */
    public function render($name, $options = array())
    {
        if (!is_string($name)
            || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name)) {
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
        if (!is_file($path)) {
            throw new InvalidArgumentException('icon_name_unknown');
        }
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
