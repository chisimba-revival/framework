<?php

$GLOBALS['kewl_entry_point_run'] = true;

class ChisimbaObject
{
    public $objConfig;
}

require_once dirname(__DIR__) . '/classes/fileapi_class_inc.php';

$property = new ReflectionProperty(ChisimbaObject::class, 'objConfig');
if (!$property->isPublic()) {
    fwrite(STDERR, "ChisimbaObject::objConfig must remain public.\n");
    exit(1);
}

echo "fileapi PHP 8.5 visibility contract passed\n";
