<?php
namespace App\Middleware;

use App\Helpers\ArrayHelper;


use Phalcon\Mvc\Micro\MiddlewareInterface;

/**
 * Слой, отвечающий за вывод результата клиенту.
 * Class ResponseMiddleware
 * @package App\Middleware
 */
class ResponseMiddleware implements MiddlewareInterface
{

    /**
     * Calls the middleware
     *
     * @param \Phalcon\Mvc\Micro $application
     * @throws \App\Exceptions\HttpException
     */
    public function call(\Phalcon\Mvc\Micro $application)
    {
        $result = $application->getReturnedValue();

        $type = ArrayHelper::getValue($application->request->get(), "type", "json");

        if ($type == "json") {
            $response = new \App\Responses\JsonResponse();
        } else {
            throw new \App\Exceptions\HttpException(
                'Could not return results in specified format',
                403,
                array(
                    'dev' => 'Could not understand type specified by type parameter in query string',
                    'internalCode' => 'NF1000',
                    'more' => 'Type may not be implemented. Choose either "csv" or "json"'
                )
            );
        }

        $response->send($result, false, ['requestId' => REQUEST_ID]);
    }
}