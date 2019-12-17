<?php
use Phalcon\Config\Adapter\Yaml;

$config = new Yaml(APP_DIR . "/app/config/config.yml");

if (file_exists(APP_DIR . "/app/config/config-local.yml")) {
    $localConfig = new Yaml(APP_DIR . "/app/config/config-local.yml");
    $config->merge($localConfig);
}

$appPaths = $config->path('application');

foreach ($appPaths as $key => &$appPath) {
    if (substr($key, -3) == "Dir") {
        $appPath = APP_DIR . '/' . $appPath;
    }
}

unset($appPath);

$config->application = $appPaths;

/**
 * Инициализация сервисов.
 * Некоторые сервисы инициализируются в бутстрапах модулей.
 */
include __DIR__ . "/services.php";

/**
 * Инициализация лоадера.
 */
include __DIR__ . "/loader.php";


$di->set('config', function () use ($config) {
    return $config;
});

