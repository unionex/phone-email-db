<?php
namespace App\Exceptions;

/**
 * Исключение, которое выбрасывается в случае, когда пользователь передал неверные данные.
 * Class BadRequestException
 * @package App\Exceptions
 */
class BadRequestException extends HttpException
{
    /**
     * BadRequestException constructor.
     * @param string|null $message
     */
    public function __construct($message = null)
    {
        if ($message == null) {
            $message = "Bad Request";
        }

        parent::__construct($message, 400, []);
    }
}