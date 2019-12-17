<?php
namespace App\Middleware;

use Phalcon\Mvc\Micro\MiddlewareInterface;

/**
 * Слой, работающий в случае, если не найден маршрут.
 * Class NotFoundMiddleware
 * @package App\Middleware
 */
class NotFoundMiddleware implements MiddlewareInterface
{
    /**
     * Calls the middleware
     *
     * @param \Phalcon\Mvc\Micro $application
     * @throws \App\Exceptions\HttpException
     */
    public function call(\Phalcon\Mvc\Micro $application)
    {
        throw new \App\Exceptions\HttpException(
            'Not Found',
            404,
            array(
                'dev' => 'That route was not found on the server',
                'internalCode' => 'NF1000',
                'more' => 'Check route for misspellings'
            )
        );
    }
}