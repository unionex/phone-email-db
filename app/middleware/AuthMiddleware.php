<?php
namespace App\Middleware;

use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Models\Session;
use App\Models\User;
use App\Modules\Acl\Services\AuthManager;

use Phalcon\Http\Request;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface;

/**
 * Слой авторизации. Проверяет токен в хэдере Authorization и, в случае успеха, устанавливает текущего пользователя
 * с таким токеном.
 * Class AuthMiddleware
 * @package App\Middleware
 * @see AuthManager::setIdentity()
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Calls the middleware
     *
     * @param Micro $application
     * @throws HttpException
     */
    public function call(Micro $application)
    {
        /** @var Request $request */
        $request = $application->getDI()->getShared("request");
        /** @var AuthManager $authManager */
        $authManager = $application->getDI()->getShared("auth");

        $token = $request->getHeader("Authorization");

        if ($token && preg_match('/Bearer\s(\S+)/', $token, $matches)) {
            $token = $matches[1];

            /** @var Session $session */
            $session = Session::findFirst([
                "token = :token:",
                "bind" => [
                    "token" => $token
                ]
            ]);

            if (!$session) {
                throw new NotFoundException(Session::class);
            }

            if ($session->expireAt->getTimestamp() < time()) {
                throw new HttpException("Token is expired", 401, []);
            }

            if ($session->expireAt->getTimestamp() < time() - (15 * 60)) {
                $session->expireAt->setTimestamp(time() + (15 * 60));
                $session->update();
            }

            $authManager->authorize($session);

            return;
        }

        return;
    }
}