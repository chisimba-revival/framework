<?php
/** Shared maintained purifier for legacy authored HTML and formatted previews. */
final class ChisimbaHtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            require_once dirname(__DIR__, 2) . '/lib/HTMLPurifier.auto.php';
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            // Releases are read-only. Reuse definitions within the request rather
            // than writing serialised vendor caches into the application tree.
            $config->set('Cache.DefinitionImpl', null);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            self::$purifier = new HTMLPurifier($config);
        }
        return self::$purifier;
    }
}
