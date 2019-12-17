<?php
namespace App\Exceptions;

use Phalcon\Mvc\Model\MessageInterface as ModelMessageInterface;
use Phalcon\Validation\MessageInterface as ValidationMessageInterface;
use Phalcon\Validation\Message\Group;

/**
 * Исключение, которое вызывается в случае, когда модель не прошла валидацию.
 * В конструктор может быть передан массив с сообщениями, либо коллекция, либо стандартный массив.
 * Клиенту выводится только первая ошибка.
 * Пример:
 * ```php
 * $user = User::FindFirst(1);
 * $user->email = "validationFailed"; // некорректная эл. почта
 * if (!$user->save()) {
 *     throw new ValidationFailedException($user->getMessages());
 * }
 * ```
 * Class ValidationFailedException
 * @package App\Exceptions
 */
class ValidationFailedException extends HttpException
{
    /**
     * ValidateException constructor.
     * @param ModelMessageInterface[]|Group|array $messages
     */
    public function __construct($messages)
    {
        $message = $messages[0];

        if ($message instanceof ModelMessageInterface || $message instanceof ValidationMessageInterface) {
            $message = [
                'type' => $message->getType(),
                'field' => $message->getField(),
                'message' => $message->getMessage()
            ];
        } else {
            $message = [
                'type' => $message['type'],
                'field' => $message['field'],
                'message' => $message['message']
            ];
        }

        parent::__construct($message['message'], 400, ['more' => $message]);
    }
}