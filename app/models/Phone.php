<?php
namespace App\Models;

/**
 * Модель телефона.
 * Class Phone
 * @package App\Models
 */
class Phone extends \Phalcon\Mvc\Model
{
    /**
     * @var int ID.
     */
    public $id;

    /**
     * @var string номер телефона.
     */
    public $number;

    /**
     * Инициализация модели.
     */
    public function initialize() : void
    {
        $this->setSource("phones");
    }

    /**
     * Валидация модели.
     * @return bool
     */
    public function validation() : bool
    {
        $validator = new \Phalcon\Validation();

        return $this->validate($validator);
    }

    /**
     * Маппинг столбцов в таблице.
     * @return array
     */
    public function columnMap() : array
    {
        return [
            "id" => "id",
            "number" => "number"
        ];
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            "id" => (int) $this->id,
            "number" => $this->number
        ];
    }
}