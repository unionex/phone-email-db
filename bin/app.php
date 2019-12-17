<?php

error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) {
    echo sprintf("%s in %s on line %d\n", $e->getMessage(), $e->getFile(), $e->getLine());
    echo $e->getTraceAsString();
});

set_error_handler(function (int $number, string $error, string $file, int $line, array $context) {
    $cat = [
        E_ERROR => "error",
        E_RECOVERABLE_ERROR => "recoverable_error",
        E_WARNING => "warning",
        E_PARSE => "parse",
        E_NOTICE => "notice",
        E_STRICT => "strict",
        E_DEPRECATED => "deprecated",
        E_CORE_ERROR => "core_error",
        E_CORE_WARNING => "core_warning",
        E_COMPILE_ERROR => "compile_error",
        E_COMPILE_WARNING => "compile_warning",
        E_USER_ERROR => "user_error",
        E_USER_WARNING => "user_warning",
        E_USER_NOTICE => "user_notice",
        E_USER_DEPRECATED => "user_deprecated"
    ];

    echo sprintf("[%s] %s in %s on line %d\n", $cat[$number], $error, $file, $line);
});

define("REQUEST_ID", "console");
define("APP_DIR", realpath(__DIR__ . "/.."));

use Phalcon\Cli\Console as ConsoleApp;

if (file_exists(APP_DIR . "/vendor/autoload.php")) {
    include APP_DIR . "/vendor/autoload.php";
}

include APP_DIR . '/app/config/bootstrap.php';

$di->set('dispatcher', function () {
    $dispatcher = new \Phalcon\Cli\Dispatcher();

    $dispatcher->setDefaultNamespace('App\\Cli\\Tasks');

    return $dispatcher;
});

$loader->registerDirs(
    [
        APP_DIR . '/app/cli/tasks',
    ]
);

$loader->registerNamespaces(
    array_merge(
        $loader->getNamespaces(),
        [
            'App\\Cli' => APP_DIR . '/app/cli',
            'App\\Cli\\Tasks' => APP_DIR . '/app/cli/tasks'
        ]
    )
);

$console = new ConsoleApp();

$console->setDI($di);

$console->setEventsManager(new \Phalcon\Events\Manager());

$console->getEventsManager()->attach(
    "console:beforeHandleTask",
    function (\Phalcon\Events\Event $event, ConsoleApp $console) : bool {
        /** @var \Phalcon\Cli\Dispatcher $dispatcher */
        $dispatcher = $event->getData();

        $allowed = [
            "import:phone",
            "import:email",
            "eject:phone",
            "eject:email"
        ];

        $action = sprintf("%s:%s", $dispatcher->getTaskName(), $dispatcher->getActionName());

        if (!in_array($action, $allowed)) {
            return true;
        }

        echo "Executing cli task... ({$action})" . PHP_EOL;
        echo "Date: " . date("d.m.Y H:i:s") . PHP_EOL;
        echo "Args: ";
        var_dump($dispatcher->getParams());
        echo PHP_EOL;

        return true;
    }
);

$arguments = [];

foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments['task'] = $arg;
    } elseif ($k === 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

try {
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    fwrite(STDERR, $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL);
    exit(1);
} catch (\Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . " in " . $throwable->getFile() . " on line " . $throwable->getLine() . PHP_EOL);
    exit(1);
} catch (\Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . PHP_EOL);
    exit(1);
}
