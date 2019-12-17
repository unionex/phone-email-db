<?php
namespace App\Models;

use App\Helpers\FilterHelper;
use App\Helpers\StringHelper;
use App\Modules\Acl\Services\AuthManager;
use DateTime;

use Phalcon\Mvc\Model;
use Phalcon\Validation;

/**
 * Модель пользователя.
 * Class User
 * @package App\Models
 * @property-read File $photo
 */
class User extends Model
{
    /**
     * Роль неавторизованного пользователя
     */
    const R_GHOST = 'Ghost';
    /**
     * Роль администратора.
     */
    const R_ADMIN = 'Admin';
    /**
     * Пользователь активен.
     */
    const ACTIVE = 1;
    /**
     * Пользователь деактивирован.
     */
    const INACTIVE = 0;

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $mainRoleId;

    /**
     *
     * @var string логин.
     */
    public $login;

    /**
     * @var string имя.
     */
    public $firstName;

    /**
     * @var string фамилия.
     */
    public $secondName;

    /**
     * @var string отчество.
     */
    public $patronymic;

    /**
     * @var string хэш пароля.
     */
    public $password;

    /**
     *
     * @var string электронная почта.
     */
    public $email;

    /**
     * @var string токен.
     */
    public $token;

    /**
     * @var DateTime дата истечения токена.
     */
    public $tokenExpiredDate;

    /**
     * @var string номер телефона.
     */
    public $phone;

    /**
     * @var int
     */
    public $photoId;

    /**
     * @var int пользователь активен.
     */
    public $active;

    /**
     * @var DateTime дата создания пользователя.
     */
    public $createdAt;

    /**
     * @var DateTime дата обновления пользователя.
     */
    public $updatedAt;

    /**
     * Проверяет, имеет ли пользователь роль с ID $roleId.
     * @param string $roleId
     * @return bool
     */
    public function hasRole($roleId)
    {
        /** @var AuthManager $auth */
        $auth = $this->getDI()->getShared('auth');

        $role = $auth->getRoleById($roleId);

        return $role && $auth->hasRole($this, $role);
    }

    /**
     * Возвращает основную роль.
     * @return \Phalcon\Acl\RoleInterface
     */
    public function getMainRole()
    {
        /** @var AuthManager $auth */
        $auth = $this->getDI()->getShared('auth');

        return $auth->getRoleById($this->mainRoleId);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        /** @var AuthManager $auth */
        $auth = $this->getDI()->getShared("auth");

        $mainRole = $this->getMainRole();

        $roles = [];
        foreach ($auth->getRoles($this) as $role) {
            $roles[] = [
                'name' => $role->getName(),
                'description' => $role->getDescription()
            ];
        }

        return [
            'id' => (int) $this->id,
            'roles' => $roles,
            'mainRole' => [
                'name' => $mainRole->getName(),
                'description' => $mainRole->getDescription()
            ],
            'login' => $this->login,
            'firstName' => $this->firstName,
            'secondName' => $this->secondName,
            'patronymic' => $this->patronymic,
            'email' => $this->email,
            'phone' => $this->phone,
            'photoId' => $this->photoId ? (int) $this->photoId : null,
            'studentId' => $studentId ?? null,
            'teacherId' => $teacherId ?? null,
            'active' => $this->active == static::ACTIVE ? true : false
        ];
    }

    /**
     * Создает уникальный логин исходя из имени, фамилии и отчества.
     * @param string $firstName
     * @param string $secondName
     * @param string|null $patronymic
     * @return string
     */
    public static function buildLogin($firstName, $secondName, $patronymic = null)
    {
        if ($patronymic) {
            $login = sprintf(
                "%s_%s%s",
                $secondName,
                substr($firstName, 0, 1),
                substr($patronymic, 0, 1)
            );
        } else {
            $login = sprintf(
                "%s_%s",
                $secondName,
                substr($firstName, 0, 1)
            );
        }

        $login = FilterHelper::lower($login);
        $login = StringHelper::transliteration($login);

        $user = User::findFirst([
            '(LCASE(login) = :login:)',
            'bind' => [
                'login' => $login
            ]
        ]);


        if ($user) {
            $newLogin = "";

            $iterator = 2;

            while ($user) {
                $newLogin = $login . $iterator;

                $user = User::findFirst([
                    '(LCASE(login) = :login:)',
                    'bind' => [
                        'login' => $newLogin
                    ]
                ]);

                $iterator ++;
            }

            $login = $newLogin;
        }

        return $login;
    }

    /**
     * Возвращает ФИО одной строкой.
     * @return string
     */
    public function getFullName()
    {
        if ($this->patronymic) {
            return sprintf("%s %s %s", $this->firstName, $this->secondName, $this->patronymic);
        }

        return sprintf("%s %s", $this->firstName, $this->secondName);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        if ($this->id) {
            $changedFields = $this->getChangedFields();

            if (in_array("photoId", $changedFields)) {
                $snapshot = $this->getOldSnapshotData();
                $oldPhotoId = $snapshot['photoId'];

                if ($oldPhotoId) {
                    (File::findFirst($oldPhotoId))->delete();
                }
            }
        }
    }

    /**
     * Инициализация модели.
     */
    public function initialize()
    {
        $this->setSource("users");
        $this->keepSnapshots(true);

        $this->addBehavior(new Model\Behavior\Timestampable([
            'beforeCreate' => [
                'field' => 'createdAt',
                'format' => 'Y-m-d H:i:s'
            ],
            'beforeUpdate' => [
                'field' => 'updatedAt',
                'format' => 'Y-m-d H:i:s'
            ]
        ]));

        $this->addBehavior(new \App\Behaviors\DateTime([
            'property' => 'createdAt'
        ]));

        $this->addBehavior(new \App\Behaviors\DateTime([
            'property' => 'updatedAt'
        ]));

        $this->hasOne('photoId', File::class, 'id', ['alias' => 'photo']);
    }

    /**
     * Маппинг свойств.
     * @return array
     */
    public function columnMap()
    {
        return [
            'id' => 'id',
            'main_role_id' => 'mainRoleId',
            'login' => 'login',
            'first_name' => 'firstName',
            'second_name' => 'secondName',
            'patronymic' => 'patronymic',
            'password' => 'password',
            'email' => 'email',
            'token' => 'token',
            'token_expired_date' => 'tokenExpiredDate',
            'phone' => 'phone',
            'photo_id' => 'photoId',
            'active' => 'active',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt'
        ];
    }

    /**
     * Валидация модели/
     * @return bool
     */
    public function validation()
    {
        $validator = new Validation();

        $validator->add(
            'firstName',
            new Validation\Validator\PresenceOf([
                'message' => 'Имя обязательно к заполнению'
            ])
        );

        $validator->add(
            'secondName',
            new Validation\Validator\PresenceOf([
                'message' => 'Фамилия обязательна к заполнению'
            ])
        );

        $validator->add(
            'email',
            new Validation\Validator\Email([
                'message' => 'Некорректный E-Mail',
                'allowEmpty' => true
            ])
        );

        $validator->add(
            'email',
            new Validation\Validator\Uniqueness([
                'message' => 'Пользователь с таким E-Mail уже существует',
                'allowEmpty' => true
            ])
        );

        $validator->add(
            'phone',
            new Validation\Validator\Regex([
                'message' => 'Некорректный номер телефона',
                'pattern' => '/^\+[0-9]{1,3}[0-9]{10}$/',
                'allowEmpty' => true
            ])
        );

        $validator->add(
            'login',
            new Validation\Validator\Regex([
                'message' => 'Некорректный логин',
                'pattern' => '/^[a-zA-Z0-9_-]*$/',
                'allowEmpty' => true
            ])
        );

        $validator->add(
            'login',
            new Validation\Validator\StringLength([
                'messageMinimum' => 'Длина логина должна быть больше :min символов',
                'messageMaximum' => 'Длина логина должна быть больше :max символов',
                'min' => 4,
                'max' => 12
            ])
        );

        $validator->add(
            'login',
            new Validation\Validator\Uniqueness([
                'message' => 'Логин занят'
            ])
        );

        $validator->add(
            'active',
            new Validation\Validator\Callback([
                'message' => 'Неверные данные для поля "Активен"',
                'callback' => function ($data) {
                    $data = $data->active;

                    return is_numeric($data) && ($data == User::ACTIVE || $data == User::INACTIVE);
                }
            ])
        );

        return $this->validate($validator);
    }
}
