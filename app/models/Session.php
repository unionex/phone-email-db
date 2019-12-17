<?php
namespace App\Models;

use App\Helpers\ArrayHelper;
use Phalcon\Mvc\Model;

/**
 * Модель сессии пользователя.
 * Class Session
 * @package App\Models
 * @property-read User $user
 */
class Session extends Model
{
    /**
     * @var string токен авторизации.
     */
    public $token;

    /**
     * @var int ID пользователя.
     */
    public $userId;

    /**
     * @var array данные сессии.
     */
    public $data;

    /**
     * @var \DateTime дата создания.
     */
    public $createdAt;

    /**
     * @var \DateTime дата изменения.
     */
    public $updatedAt;

    /**
     * @var \DateTime дата истечения.
     */
    public $expireAt;

    /**
     * Установить значение парамераметра, который будет храниться в сессии.
     * @param string|array $param название ключа. Можно разделять точками (если строка) или передавать массивом.
     * @param mixed $value значение параметра.
     */
    public function set($param, $value)
    {
        ArrayHelper::setValue($this->data, $param, $value);
    }

    /**
     * Возвращает значение параметра, который хранится в сессии.
     * @param string|array $param название ключа. Можно разделять точками (если строка) или передавать массивом.
     * @param mixed $defaultValue дефолтное значение параметра (на случай, если искомый параметр не задан).
     * @return mixed
     */
    public function get($param, $defaultValue = null)
    {
        return ArrayHelper::getValue($this->data, $param, $defaultValue);
    }

    public function columnMap()
    {
        return [
            "token" => "token",
            "user_id" => "userId",
            "data" => "data",
            "created_at" => "createdAt",
            "updated_at" => "updatedAt",
            "expire_at" => "expireAt"
        ];
    }

    public function initialize()
    {
        $this->setSource("sessions");

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

        $this->addBehavior(new \App\Behaviors\JsonSerialize([
            'property' => 'data'
        ]));

        $this->addBehavior(new \App\Behaviors\DateTime([
            'property' => 'createdAt'
        ]));

        $this->addBehavior(new \App\Behaviors\DateTime([
            'property' => 'updatedAt'
        ]));

        $this->addBehavior(new \App\Behaviors\DateTime([
            'property' => 'expireAt'
        ]));

        $this->belongsTo('userId', User::class, 'id', ['alias' => 'user']);
    }
}