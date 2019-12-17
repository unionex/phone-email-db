<?php
namespace App\Behaviors;

use Phalcon\Mvc\Model\Behavior;

/**
 * Поведение, с помощью которого можно переводить в строку и обратно объекты типа DateTime.
 * Class DateTime
 * @package App\Behaviors
 */
class DateTime extends Behavior
{
    public function notify($type, \Phalcon\Mvc\ModelInterface $model)
    {
        $config = $this->getOptions();
        $property = $config['property'];

        $property = &$model->$property;

        if ($type == "beforeSave") {
            if ($property != null && $property instanceof \DateTime) {
                $property = $property->format('Y-m-d H:i:s');
            }
        } elseif ($type == "afterSave" || $type == "afterFetch") {
            if ($property !== null && is_string($property)) {
                $property = new \DateTime($property);
            }
        }
    }
}