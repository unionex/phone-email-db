<?php

use App\Helpers\ArrayHelper;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Postgresql as DbAdapter;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter as LoggerAdapter;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Di\FactoryDefault\Cli as CliFactoryDefault;

if (php_sapi_name() == "cli") {
    $di = new CliFactoryDefault();
} else {
    $di = new FactoryDefault();
}

$di->setInternalEventsManager(new EventsManager());

/**
 * Менеджер моделей.
 */
$di->setShared("modelsManager", function () {
    return new ModelsManager();
});

/**
 * Менеджер мета-данных моделей.
 */
$di->setShared("modelsMetadata", function () use ($config) {
    if ($config->modelsMeta->cache === false) {
        $manager = new \Phalcon\Mvc\Model\MetaData\Memory();
    } else {
        $adapter = $config->modelsMeta->cache->adapter;
        $adapterConfig = $config->modelsMeta->cache->config;
        $class = sprintf("Phalcon\\Mvc\\Model\\MetaData\\%s", $adapter);

        $manager = new $class(ArrayHelper::toArray($adapterConfig));
    }

    $manager->setStrategy(
        new Phalcon\Mvc\Model\MetaData\Strategy\Introspection()
    );

    return $manager;
});

/**
 * Сервис авторизации.
 */
$di->setShared("auth", function () use ($config) {
    $authManager = new \App\Modules\Acl\Services\AuthManager();
    $authManager->setAclService($config->auth->aclService);
    $authManager->setDbService($config->auth->dbService);

    if (in_array("auth", $config->logger->logging->toArray())) {
        $authManager->getEventsManager()->attach("auth:afterAuthorize", function (Event $event) {
            /** @var LoggerAdapter $logger */
            $logger = \Phalcon\Di::getDefault()->getShared('logger');

            /** @var \App\Models\Session $session */
            $session = $event->getData()['session'];

            $logger->info("[auth] Authorized through user {username} [{id}]", [
                'username' => $session->user->login,
                'id' => $session->userId
            ]);
        });

        $authManager->getEventsManager()->attach("auth:afterStartSession", function (Event $event) {
            /** @var LoggerAdapter $logger */
            $logger = \Phalcon\Di::getDefault()->getShared('logger');

            /** @var \App\Models\User $user */
            $user = $event->getData()['user'];

            $logger->info("[auth] Session started for user {username} [{id}]", [
                'username' => $user->login,
                'id' => $user->id
            ]);
        });
    }

    return $authManager;
});

/**
 * Сервис обмена.
 */
$di->setShared("exchange", function () use ($config, $di) {
    $exchangeService = new \App\Modules\Exchange\Services\ExchangeService([
        'host' => $config->exchange->host,
        'secret' => $config->exchange->secret,
        'licenseKey' => function () use ($di) {
            /** @var \App\Services\SettingsManager $settings */
            $settings = $di->getShared("settings");

            /** @var \App\Models\KeyLicense $key */
            $key = \App\Models\KeyLicense::findFirst([
                'key = :key:',
                'bind' => [
                    'key' => $settings->get('license')
                ]
            ]);

            return $key ? $key->key : null;
        },
        'updateService' => function () use ($di) {
            return $di->getShared("update");
        },
        'testLoaderService' => function () use ($di) {
            return $di->getShared("testLoader");
        }
    ]);

    if (in_array("exchange", $config->logger->logging->toArray())) {
        $exchangeService->getEventsManager()->attach("exchange:afterRequest",
            function (Event $event, \App\Modules\Exchange\Services\ExchangeService $exchangeService) {
                $data = $event->getData();

                /** @var LoggerAdapter $logger */
                $logger = \Phalcon\Di::getDefault()->getShared('logger');

                $logger->info("[exchange] {method} {uri}:\n{options}\n{result}", [
                        'method' => $data['method'],
                        'uri' => $data['uri'],
                        'options' => print_r($data['options'], true),
                        'result' => $data['result']
                    ]);
            });
    }

    return $exchangeService;
});


/**
 * Сервис для работы с персональной БД.
 */
$di->set('db', function () use ($config) {
    $adapter = new DbAdapter(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname
    ));

    $adapter->setEventsManager(\Phalcon\Di::getDefault()->getShared('eventsManager'));

    return $adapter;
});

/**
 * Сервис для работы с кешем.
 */
$di->set('cache', function () use ($config) {
    $cacheConfig = $config->cache;

    if (isset($cacheConfig->frontend) && isset($cacheConfig->backend)) {
        $frontCache = \Phalcon\Cache\Frontend\Factory::load($cacheConfig->frontend);

        $backCache = \Phalcon\Cache\Backend\Factory::load(
            array_merge(
                $cacheConfig->backend->toArray(),
                ['frontend' => $frontCache]
            )
        );

        return $backCache;
    }

    $cacheAdapters = [];

    if (isset($cacheConfig->file)) {
        $cacheDir = &$cacheConfig->file->backend->cacheDir;

        $cacheDir = APP_DIR . "/" . $cacheDir . "/";

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        unset($cacheDir);
    }

    foreach ($cacheConfig as $adapter => $adapterConfig) {
        $frontCache = \Phalcon\Cache\Frontend\Factory::load($adapterConfig->frontend);

        $backCache = \Phalcon\Cache\Backend\Factory::load(
            array_merge(
                $adapterConfig->backend->toArray(),
                ['adapter' => $adapter, 'frontend' => $frontCache]
            )
        );

        $cacheAdapters[] = $backCache;
    }

    return new \Phalcon\Cache\Multiple($cacheAdapters);
});

/**
 * Сервис по работе с безопасностью.
 */
$di->set('security', function () {
    $security = new \Phalcon\Security();

    $security->setWorkFactor(12);

    return $security;
});

