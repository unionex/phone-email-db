<?php
use Phalcon\Annotations\Adapter\Memory as MemoryAdapter;

/** @var \Phalcon\Config $config */
/** @var \Phalcon\Loader $loader */
/** @var \Phalcon\Mvc\Micro $app */

/** @var \Phalcon\Cache\Backend $cache */
$cache = \Phalcon\Di::getDefault()->getShared('cache');

if ($cache->exists('router.routes')) {
    $routes = $cache->get('router.routes');
} else {
    $routes = [];

    $controllersDir = $config->application->controllersDir;

    $controllersFiles = scandir($controllersDir);
    $controllersFiles = array_values(array_diff($controllersFiles, ['.', '..']));

    $namespaces = array_map(function ($item) {
        return $item[0];
    }, $loader->getNamespaces());

    $namespace = array_search($controllersDir, $namespaces);

    $adapter = new MemoryAdapter();

    foreach ($controllersFiles as $controller) {
        $class = $namespace . '\\' . substr($controller, 0, -4);

        if (!class_exists($class)) {
            continue;
        }

        $reflectionData = $adapter->get($class)->getReflectionData();

        if (!isset($reflectionData['methods'])) {
            continue;
        }

        $methods = array_keys($reflectionData['methods']);

        foreach ($methods as $method) {
            $annotations = $adapter->getMethod($class, $method);

            foreach ($annotations as $annotation) {
                if (!in_array($annotation->getName(), ['Get', 'Post', 'Put', 'Delete', 'Patch', 'Options'])) {
                    continue;
                }

                $route = $annotation->getArgument(0);

                $routes[$class][$route][$annotation->getName()] = $method;
            }
        }
    }

    $cache->save('router.routes', $routes);
}

$collections = [];

foreach ($routes as $handler => $handlerData) {
    if (empty($handlerData)) {
        continue;
    }

    $collection = new \Phalcon\Mvc\Micro\Collection();
    $collection->setHandler($handler);
    $collection->setLazy(true);

    foreach ($handlerData as $route => $routeData) {
        foreach ($routeData as $httpMethod => $classMethod) {
            $collection->$httpMethod($route, $classMethod);
        }

        $app->options($route, function () use ($routeData, $app) {
            $methods = array_keys($routeData);
            $methods = implode(",", $methods);
            $methods = strtoupper($methods);

            $app
                ->response
                ->setHeader(
                    "Access-Control-Allow-Methods",
                    $methods
                );
        });
    }

    $collections[] = $collection;
}

return $collections;