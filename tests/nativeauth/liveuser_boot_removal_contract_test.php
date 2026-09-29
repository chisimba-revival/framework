<?php

function ensureLiveUserBootRemoval($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
$engine = file_get_contents($root . '/app/classes/core/engine_class_inc.php');
$object = file_get_contents($root . '/app/classes/core/object_class_inc.php');
$modules = file_get_contents(
    $root . '/app/core_modules/modulecatalogue/classes/modulesadmin_class_inc.php'
);
$catalogue = file_get_contents(
    $root . '/app/core_modules/modulecatalogue/controller.php'
);
$permissions = file_get_contents(
    $root . '/app/core_modules/permissions/classes/perms_class_inc.php'
);

ensureLiveUserBootRemoval(
    strpos($engine, "getPearResource ( 'LiveUser.php' )") === false
    && strpos($engine, "getPearResource ( 'LiveUser/Admin.php' )") === false,
    'Engine must not load LiveUser classes during request boot'
);
ensureLiveUserBootRemoval(
    strpos($engine, '$this->getLU ();') === false,
    'Engine must not initialise LiveUser during request boot'
);
ensureLiveUserBootRemoval(
    strpos($engine, 'LiveUser::singleton') === false
    && strpos($engine, 'LiveUser_Admin::factory') === false,
    'No engine endpoint may initialise the retired LiveUser runtime'
);
ensureLiveUserBootRemoval(
    strpos($engine, "getPearResource ( 'Event/Dispatcher.php' )") !== false,
    'Engine must load Event_Dispatcher without LiveUser'
);
ensureLiveUserBootRemoval(
    strpos($engine, '$this->appid = \'chisimba\';') !== false,
    'Engine must use the canonical permission namespace'
);
ensureLiveUserBootRemoval(
    strpos($object, 'getLU()') === false,
    'Base objects must not reinitialise LiveUser'
);
ensureLiveUserBootRemoval(
    strpos($modules, 'objLuAdmin') === false
    && strpos($catalogue, 'objLuAdmin') === false,
    'Module uninstall must not use LiveUser administration'
);
ensureLiveUserBootRemoval(
    strpos($permissions, 'checkRight(') === false
    && strpos($permissions, 'outputRightsConstants') === false,
    'Permission compatibility facade must use native services'
);

echo "LiveUser boot removal contract passed\n";
