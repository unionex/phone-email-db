<?php
namespace App\Models;

/**
 * Модель Эл. почты.
 * Class Email
 * @package App\Models
 */
class Email extends \Phalcon\Mvc\Model
{
    /**
     * @var int ID.
     */
    public $id;

    /**
     * @var string эл. почта.
     */
    public $email;

    /**
     * Инициализация модели.
     */
    public function initialize() : void
    {
        $this->setSource("emails");
    }

    /**
     * Маппинг.
     * @return array
     */
    public function columnMap() : array
    {
        return [
            "id" => "id",
            "email" => "email"
        ];
    }

    /**
     * Валидация модели.
     * @return bool
     */
    public function validation() : bool
    {
        $validation = new \Phalcon\Validation();

        return $this->validate($validation);
    }
}