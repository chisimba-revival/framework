<?php
/** Public, site-wide identity from the canonical configuration and canvas metadata.
 * Context/user canvas overrides deliberately do not affect initial app branding.
 * @author Derek Keats
 */
if (!$GLOBALS['kewl_entry_point_run']) { die('You cannot view this page directly'); }
class sitebranding extends ChisimbaObject
{
    public function getBranding(): array
    {
        $config = $this->getObject('altconfig', 'config');
        $skin = (string) $config->getdefaultSkin();
        $canvas = (string) $this->getObject('dbsysconfig', 'sysconfig')->getValue('canvas_preferredcanvas', 'canvas');
        if ($skin === 'kenga-learn') { $skin = 'chisimba-reborn'; $canvas = 'kenga-learn'; }
        $name = trim(strip_tags((string) $config->getSiteName()));
        $result = ['version' => 1, 'siteName' => mb_substr($name ?: 'Chisimba', 0, 160),
            'canvas' => null, 'colours' => ['primary' => null, 'accent' => null], 'logo' => null];
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $skin)) { return $result; }
        $root = realpath(rtrim($config->getsiteRootPath(), '/').'/skins/'.$skin);
        if ($root === false) { return $result; }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $canvas)) { $canvas = '_default'; }
        $directory = realpath($root.'/canvases/'.$canvas);
        if ($directory === false || !str_starts_with($directory, $root.'/canvases/')) {
            $canvas = '_default'; $directory = realpath($root.'/canvases/_default');
        }
        if ($directory === false || !str_starts_with($directory, $root.'/canvases/')) { return $result; }
        $metadata = $directory.'/canvas.json';
        if (!is_file($metadata) || filesize($metadata) > 32768) { return $result; }
        $data = json_decode(file_get_contents($metadata), true);
        if (!is_array($data)) { return $result; }
        $result['canvas'] = $canvas;
        foreach (['primary', 'accent'] as $key) {
            $value = $data['brand'][$key] ?? null;
            if (is_string($value) && preg_match('/^#[a-fA-F0-9]{6}$/D', $value)) {
                $result['colours'][$key] = strtolower($value);
            }
        }
        // Only declared public raster assets within this skin. Never fetch URLs,
        // expose paths, or return executable SVG/HTML from branding metadata.
        $relative = $data['preview']['logo'] ?? null;
        if (!is_string($relative) || str_contains($relative, "\0") || str_contains($relative, ':')) { return $result; }
        $logo = realpath($directory.'/'.$relative);
        if ($logo === false || !str_starts_with($logo, $root.'/') || !is_file($logo)
            || filesize($logo) > 196608) { return $result; }
        $bytes = file_get_contents($logo);
        $size = @getimagesizefromstring($bytes);
        if (!$size || $size[0] > 2048 || $size[1] > 2048
            || !in_array($size['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) { return $result; }
        $result['logo'] = ['mime' => $size['mime'], 'base64' => base64_encode($bytes)];
        return $result;
    }
}
