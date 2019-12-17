<?php
namespace App\Exceptions;


/**
 * Исключение, которое вызывается в случае, когда пользователь не передал какой-либо обязательный параметр.
 * Class MissingParameterException
 * @package App\Exceptions
 */
class MissingParameterException extends HttpException
{
    /**
     * MissingParameterException constructor.
     * @param string $parameter
     */
    public function __construct($parameter)
    {
        parent::__construct($parameter . ' Is Required', 400, []);
    }
}