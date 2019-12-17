<?php
namespace App\Cli\Tasks;

use App\Cli\Task;
use App\Models\User;
use App\Modules\Acl\Services\AuthManager;

class UserTask extends Task
{
    /**
     * Генерирует и возвращает токен пользователя.
     *
     * @param array $params
     * @throws \Exception
     * @throws \Exception
     */
    public function authAction($params)
    {
        $login = $params[0];

        /** @var User $user */
        $user = User::findFirst([
            "(LCASE(login) = :login: OR LCASE(email) = :login:) AND active = :active:",
            "bind" => [
                "login" => $login,
                "active" => true
            ]
        ]);


        /** @var AuthManager $auth */
        $auth = $this->getDI()->getShared("auth");
        $session = $auth->startSession($user);

        echo "Token: ", $session->token, PHP_EOL;
    }
}