<?php

error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) {
    header("HTTP/1.1 500 Internal Server Error");

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

try {
    define("REQUEST_ID", substr(md5(mt_rand() . time()), 0, 6));
    define("APP_DIR", realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'));

    if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
        include __DIR__ . '/../vendor/autoload.php';
    }

    include APP_DIR . '/app/config/bootstrap.php';

    $app = new Phalcon\Mvc\Micro();
    $app->setDI($di);
    $app->setEventsManager($di->getShared('eventsManager'));

    $collections = include __DIR__ . '/../app/config/router.php';

    foreach ($collections as $collection) {
        $app->mount($collection);
    }

    $app->before(function () use ($app) {
        $app->eventsManager->fire('app:beforeRoute', $app);
    });
    $app->after(function () use ($app) {
        $app->eventsManager->fire('app:afterRoute', $app);
    });

    $app->before(function () use ($app) {
        $middleware = new \App\Middleware\CorsMiddleware();
        $middleware->call($app);
    });

    $app->after(function () use ($app) {
        $middleware = new \App\Middleware\ResponseMiddleware();
        $middleware->call($app);
    });

    $app->notFound(function () use ($app) {
        $middleware = new App\Middleware\NotFoundMiddleware();
        $middleware->call($app);
    });

    $app->handle();
} catch (App\Exceptions\HttpException $e) {
    $app->eventsManager->fire('app:httpError', $app, [
        'errorCode' => $e->errorCode,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'message' => $e->getMessage()
    ]);

    $e->send();
} catch (\Exception $e) {
    $app->response->setStatusCode(500);
    echo sprintf("%s in %s on line %d", $e->getMessage(), $e->getFile(), $e->getLine());

    $app->response->send();
}
