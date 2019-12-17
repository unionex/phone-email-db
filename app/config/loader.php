<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    array(
        $config->application->modelsDir,
        $config->application->controllersDir,
        $config->application->exceptionsDir,
        $config->application->responsesDir,
        $config->application->middlewareDir,
        $config->application->helpersDir,
        $config->application->behaviorsDir
    )
);

$loader->registerNamespaces([
    'App\\Controllers' => $config->application->controllersDir,
    'App\\Models'      => $config->application->modelsDir,
    'App\\Exceptions'  => $config->application->exceptionsDir,
    'App\\Responses'   => $config->application->responsesDir,
    'App\\Middleware'  => $config->application->middlewareDir,
    'App\\Helpers'     => $config->application->helpersDir,
    'App\\Behaviors'   => $config->application->behaviorsDir,
    'App\\Services' => APP_DIR . "/app/services"
]);

$modules = scandir($config->application->modulesDir);
$modules = array_values(array_diff($modules, ['.', '..']));

foreach ($modules as $module) {
    if (file_exists($config->application->modulesDir . '/' . $module . '/config/bootstrap.php')) {
        include $config->application->modulesDir . '/' . $module . '/config/bootstrap.php';
    }
}

$loader->register();
