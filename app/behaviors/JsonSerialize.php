<?php
namespace App\Behaviors;

use Phalcon\Mvc\Model\Behavior;

/**
 * Поведение, с помощью которого можно переводить в строку массивы для сохранения в БД.
 * Class JsonSerialize
 * @package App\Behaviors
 */
class JsonSerialize extends Behavior
{
    public function notify($type, \Phalcon\Mvc\ModelInterface $model)
    {
        /** @var \Phalcon\Mvc\Model\MetaData $metaData */
        $metaData = \Phalcon\Di::getDefault()->getShared("modelsMetadata");

        $config = $this->getOptions();
        $property = $config['property'];

        if (!isset($config['canBeNull'])) {
            $canBeNull = !in_array($property, $metaData->getNotNullAttributes($model));
            $this->_options['canBeNull'] = $canBeNull;
        } else {
            $canBeNull = $config['canBeNull'];
        }

        $property = &$model->$property;

        if ($type == "beforeSave") {
            if ($property == null && $canBeNull) {
                $property = null;
            } else {
                $property = json_encode($property);
            }
        } elseif ($type == "afterSave" || $type == "afterFetch") {
            if ($property !== null) {
                $property = json_decode($property, true);
            }
        }
    }
}