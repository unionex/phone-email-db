<?php
namespace App\Modules\Acl\Services;

use App\Helpers\ArrayHelper;
use App\Models\Session;
use App\Models\User;
use Phalcon\Acl\Adapter as AclAdapter;
use Phalcon\Acl\RoleInterface;
use Phalcon\Db;
use Phalcon\Db\Adapter as DbAdapter;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Security;

/**
 * Менеджер авторизации. Может работать с токенами, ролями и доступами.
 * Сама авторизация происходит в App\Middleware\AuthMiddleware.
 * Токены живут 60 минут с момента регистрации, когда останется 15 минут - будут автоматически продлеваться, если пользователь активен.
 * Доступные события:
 * - auth:beforeAuthorize - перед авторизацией пользователя (можно отменить).
 * - auth:afterAuthorize - после авторизации пользователя.
 * - auth:beforeStartSession - перед стартом сессии (можно отменить, выбросится исключение).
 * - auth:afterStartSession - после старта сессии.
 * - auth:afterAppendRole - после добавления роли к пользователю.
 * - auth:afterSuspendRole - после удаления роли у пользователя.
 * Class AuthManager
 * @package App\Services
 */
class AuthManager implements InjectionAwareInterface
{
    /**
     * @var \Closure|EventsManager|string менеджер событий.
     */
    protected $eventsManager;

    /**
     * @var DiInterface dependency injector.
     */
    protected $di;

    /**
     * @var User модель текущего авторизованного пользователя.
     */
    protected $identity;

    /**
     * @var Session модель сессии текущего авторизованного пользователя.
     */
    protected $session;

    /**
     * @var \Closure|DbAdapter|string название сервиса для работы с БД.
     */
    protected $dbService;

    /**
     * @var \Closure|AclAdapter|string название сервиса для работы с ACL.
     */
    protected $aclService;

    public function __construct($config = [])
    {
        if (!isset($config['eventsManager'])) {
            $config['eventsManager'] = new EventsManager();
        }

        ArrayHelper::configure(
            $this,
            $config,
            [
                'eventsManager' => function (AuthManager $manager) use ($config) {
                    $manager->setEventsManager($config['eventsManager']);
                },
                'dbService' => function (AuthManager $manager) use ($config) {
                    $manager->setDbService($config['dbService']);
                },
                'aclService' => function (AuthManager $manager) use ($config) {
                    $manager->setAclService($config['aclService']);
                },
            ]
        );
    }

    /**
     * Возвращает роль по ее ID.
     * @param string $roleId ID (название) роли.
     * @return null|RoleInterface
     */
    public function getRoleById($roleId)
    {
        $acl = $this->getAclService();

        foreach ($acl->getRoles() as $role) {
            if ($role->getName() == $roleId) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Возвращает роли пользователя.
     * @param User $user модель пользователя.
     * @return RoleInterface[]
     */
    public function getRoles(User $user)
    {
        $connection = $this->getDbService();
        $acl = $this->getAclService();

        $userId = $user->id;

        $userRoleIds = $connection->fetchAll(
            "SELECT role_id FROM user_roles WHERE user_id = :userId",
            Db::FETCH_ASSOC,
            ['userId' => $userId]
        );

        $userRoleIds = ArrayHelper::column($userRoleIds, "role_id");

        $userRoles = [];

        foreach ($acl->getRoles() as $role) {
            if (in_array($role->getName(), $userRoleIds)) {
                $userRoles[] = $role;
            }
        }

        return $userRoles;
    }

    /**
     * Проверяет, есть ли у пользователя $user роль $role.
     * @param User $user модель пользователя.
     * @param RoleInterface $role объект роли.
     * @return bool
     */
    public function hasRole(User $user, RoleInterface $role)
    {
        $userRoles = $this->getRoles($user);

        foreach ($userRoles as $userRole) {
            if ($userRole->getName() == $role->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Привязывает роль к пользователю.
     * @param User $user модель пользователя.
     * @param RoleInterface $role объект роли.
     * @throws \Exception
     */
    public function appendRole(User $user, RoleInterface $role)
    {
        if ($this->hasRole($user, $role)) {
            throw new \Exception("User already has this role");
        }

        $connection = $this->getDbService();

        $userId = $user->id;
        $roleId = $role->getName();

        if (!$user->mainRoleId) {
            $user->mainRoleId = $roleId;
            $user->update();
        }

        $connection->insert("user_roles", [$userId, $roleId], ["user_id", "role_id"]);

        $this->getEventsManager()->fire(
            'auth:afterAppendRole',
            $this,
            [
                'role' => $role,
                'user' => $user
            ],
            false
        );
    }

    /**
     * Отвязывает пользователя от роли.
     * @param User $user модель пользователя.
     * @param RoleInterface $role объект роли.
     * @throws \Exception
     */
    public function suspendRole(User $user, RoleInterface $role)
    {
        if (!$this->hasRole($user, $role)) {
            throw new \Exception("User does not have this role");
        }

        $connection = $this->getDbService();

        $userId = $user->id;
        $roleId = $role->getName();

        $connection->delete(
            'user_roles',
            'user_id = :userId AND role_id = :roleId',
            ['userId' => $userId, 'roleId' => $roleId]
        );

        $this->getEventsManager()->fire(
            'auth:afterSuspendRole',
            $this,
            [
                'role' => $role,
                'user' => $user
            ],
            false
        );
    }

    /**
     * Проверяет, может ли пользователь совершать то или иное действие.
     * @param string $access название ресурса и название доступа.
     * @param array|null $params массив с дополнительными параметрами для проверки доступа.
     * Имеет смысл использовать только на тех доступах, в которых происходит проверка прав через обработчики (классы).
     * @param User|null $user модель пользователя, относительно которого будут проверяться доступы.
     * @return bool
     */
    public function can($access, $params = null, User $user = null)
    {
        $access = explode("!", $access);

        $resource = $access[0];
        $access = $access[1];

        /** @var AclAdapter $acl */
        $acl = $this->getDi()->getShared("acl");

        $role = null;

        if ($user || $this->getIdentity()) {
            if ($user === null) {
                $user = $this->getIdentity();
            }

            $roles = ArrayHelper::column($this->getRoles($user), function ($item) {
                /** @var RoleInterface $item */
               return $item->getName();
            });
        } else {
            $roles = [User::R_GHOST];
        }

        $params = ['user' => $user, 'params' => $params];

        foreach ($roles as $role) {
            if ($acl->isAllowed($role, $resource, $access, $params)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет правильность пароля.
     * @param string $password пароль.
     * @param User $user модель пользователя.
     * @return bool
     */
    public function checkPassword($password, User $user)
    {
        /** @var Security $security */
        $security = $this->getDi()->get("security");

        return $security->checkHash($password, $user->password);
    }

    /**
     * Генерирует, обновляет и возвращает токен авторизации.
     * @param User $user модель пользователя.
     * @return Session
     * @throws \Exception если не удалось установить\сгенерировать токен.
     */
    public function startSession(User $user) : Session
    {
        /** @var Security $security */
        $security = $this->getDi()->get("security");

        try {
            $token = $security->getRandom()->base64(20);
        } catch (Security\Exception $exception) {
            throw new \Exception("Token generation failed");
        }

        $eventResult = $this->getEventsManager()->fire(
            'auth:beforeStartSession',
            $this,
            [
                'user' => $user
            ]
        );

        if ($eventResult === false) {
            throw new \Exception("Could not create session for unknown reason");
        }

        $session = new Session();
        $session->token = $token;
        $session->userId = $user->id;
        $session->expireAt = (new \DateTime())->setTimestamp(time() + 60 * 60);
        $session->create();

        $this->getEventsManager()->fire(
            'auth:afterStartSession',
            $this,
            [
                'user' => $user
            ],
            false
        );

        return $session;
    }

    /**
     * Авторизовывает пользователя в приложении.
     * @param Session $session
     * @return bool
     */
    public function authorize(Session $session) : bool
    {
        $eventResult = $this->getEventsManager()->fire(
            'auth:beforeAuthorize',
            $this,
            [
                'session' => $session
            ]
        );

        if ($eventResult === false) {
            return false;
        }

        $this->setSession($session);
        $this->setIdentity($session->user);

        $this->getEventsManager()->fire(
            'auth:afterAuthorize',
            $this,
            [
                'session' => $session
            ],
            false
        );

        return true;
    }

    /**
     * Sets the dependency injector.
     * @param DiInterface $dependencyInjector
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector.
     * @return DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * Возвращает модель пользователя.
     * @return User
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Устанавливает модель пользователя.
     * @param User $identity
     */
    public function setIdentity(User $identity)
    {
        $this->identity = $identity;
    }

    /**
     * Возвращает модель сессии.
     * @return Session
     */
    public function getSession() : Session
    {
        return $this->session;
    }

    /**
     * Устанавливает модель сессии.
     * @param Session $session
     */
    public function setSession(Session $session) : void
    {
        $this->session = $session;
    }

    /**
     * Возвращает сервис для работы с БД.
     * @return DbAdapter
     */
    public function getDbService() : DbAdapter
    {
        if ($this->dbService instanceof \Closure) {
            return ($this->dbService)();
        } elseif (is_string($this->dbService)) {
            return $this->getDI()->getShared($this->dbService);
        }

        return $this->dbService;
    }

    /**
     * Возвращает сервис для работы с ACL.
     * @param \Closure|DbAdapter|string $dbService
     */
    public function setDbService($dbService) : void
    {
        $this->dbService = $dbService;
    }

    /**
     * Возвращает сервис для работы с ACL.
     * @return AclAdapter
     */
    public function getAclService() : AclAdapter
    {
        if ($this->aclService instanceof \Closure) {
            return ($this->aclService)();
        } elseif (is_string($this->aclService)) {
            return $this->getDI()->getShared($this->aclService);
        }

        return $this->aclService;
    }

    /**
     * Устанавливает сервис для работы с ACL.
     * @param \Closure|AclAdapter|string $aclService
     */
    public function setAclService($aclService) : void
    {
        $this->aclService = $aclService;
    }

    /**
     * Возвращает менеджер событий.
     * @return EventsManager
     */
    public function getEventsManager() : EventsManager
    {
        if ($this->eventsManager instanceof \Closure) {
            return ($this->eventsManager)();
        } elseif (is_string($this->eventsManager)) {
            return $this->getDI()->getShared($this->eventsManager);
        }

        return $this->eventsManager;
    }

    /**
     * Устанавливает менеджер событий.
     * @param \Closure|EventsManager|string $eventsManager
     */
    public function setEventsManager($eventsManager) : void
    {
        $this->eventsManager = $eventsManager;
    }
}