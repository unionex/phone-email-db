<?php
namespace App\Exceptions;

use App\Helpers\StringHelper;

/**
 * Исключение, которое вызывается в случае, если не удалось найти что-либо.
 * В конструктор можно передать класс сущности.
 * Например:
 * ```php
 * throw new NotFoundException("App\Models\StudyGroup");
 * ```
 * В итоге в браузер выкинется ошибка 404 "Study Group Not Found".
 * Class EntityNotFoundException
 * @package App\Exceptions
 */
class NotFoundException extends HttpException
{
    /**
     * NotFoundException constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        if (($pos = strrpos($name, "\\")) !== false) {
            $name = substr($name, strrpos($name, "\\") + 1);
            $name = str_replace("_", " ", StringHelper::underscore($name));
            $name = ucwords($name);
        }

        parent::__construct($name . ' Not Found', 404, []);
    }
}