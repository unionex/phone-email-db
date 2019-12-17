<?php
$loader->registerNamespaces(
    array_merge(
        $loader->getNamespaces(),
        [
            'App\\Modules\\Acl' => __DIR__ . '/..',
            'App\\Modules\\Acl\\Services' => __DIR__ . '/../services',
            'App\\Modules\\Acl\\Rules' => __DIR__ . '/../rules',
        ]
    )
);

$di->setShared("acl", function () {
    return include __DIR__ . "/acl.php";
});