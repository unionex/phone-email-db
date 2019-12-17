<?php

namespace App\Exceptions;

use App\Responses\JsonResponse;
use Phalcon\DI;
use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;

/**
 * Общее исключение, отвечающее за статусы HTTP.
 * Class HttpException
 * @package App\Exceptions
 */
class HttpException extends \Exception
{
    /**
     * @var string сообщение для разработчиков
     */
    public $devMessage;
    /**
     * @var string код ошибки (код статуса)
     */
    public $errorCode;
    /**
     * @var string описание ошибки
     */
    public $response;
    /**
     * @var mixed дополнительная информация
     */
    public $additionalInfo;

    /**
     * HttpException constructor.
     * @param string $message
     * @param int $code
     * @param array $errorArray
     */
    public function __construct($message, $code, $errorArray)
    {
        $this->message = $message;
        $this->devMessage = $errorArray['dev'] ?? null;
        $this->errorCode = $errorArray['internalCode'] ?? null;
        $this->code = $code;
        $this->additionalInfo = $errorArray['more'] ?? null;
        $this->response = $this->getResponseDescription($code);
    }

    /**
     * Отправляет ошибку клиенту.
     */
    public function send()
    {
        $di = DI::getDefault();

        /** @var ResponseInterface $response */
        $response = $di->get('response');
        /** @var RequestInterface $request */
        $request = $di->get('request');

        $response->setStatusCode($this->getCode(), $this->response)->sendHeaders();

        $error = array(
            'errorCode' => $this->getCode(),
            'userMessage' => $this->getMessage(),
            'devMessage' => $this->devMessage,
            'more' => $this->additionalInfo,
            'applicationCode' => $this->errorCode,
        );

        if (!$request->get('type') || $request->get('type') == 'json') {
            $response = new JsonResponse();
            $response->send($error, true, ['requestId' => REQUEST_ID]);
            return;
        }
    }

    /**
     * Возвращает описание результата по коду ошибки.
     * @param string $code
     * @return string
     */
    protected function getResponseDescription($code)
    {
        $codes = array(

            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );

        $result = (isset($codes[$code])) ? $codes[$code] : 'Unknown Status Code';

        return $result;
    }
}