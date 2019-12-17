<?php
namespace App\Exceptions;

use Phalcon\Di;

/**
 * Исключение, которое выбрасывается в случае, если у пользователя нет доступа.
 * В зависимости от того, авторизован пользователь или нет, может выкинуть
 * 401 (Не авторизован) или 403 (Доступ запрещен).
 * Class AccessDeniedException
 * @package App\Exceptions
 */
class AccessDeniedException extends HttpException
{
    /**
     * AccessDeniedException constructor.
     */
    public function __construct()
    {
        $di = Di::getDefault();

        if (!$di->getShared('auth')->getIdentity()) {
            parent::__construct("Unauthorized", 401, []);
        } else {
            parent::__construct("Access Denied", 403, []);
        }
    }
}