<?php
namespace App\Helpers;

use Phalcon\Mvc\Model\Query\BuilderInterface;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;

/**
 * Хэлпер, упрощающий работу с билдером запросов Phalcon.
 * Class BuilderHelper
 * @package App\Helpers
 */
class BuilderHelper
{
    /**
     * @param BuilderInterface $builder
     * @param array $sort
     * @param array|null $map
     * @deprecated
     */
    public static function orderBy(BuilderInterface &$builder, $sort, $map = null)
    {
        $orderBy = [];

        if (ArrayHelper::isIndexed($sort)) {
            $newSort = [];

            foreach ($sort as $sortData) {
                $newSort[$sortData['column']] = $sortData['order'];
            }

            $sort = $newSort;
        }

        if ($map !== null) {
            if (ArrayHelper::isAssociative($map)) {
                $newMap = [];

                foreach ($map as $key => $column) {
                    $newMap[] = [
                        'key' => $key,
                        'column' => $column
                    ];
                }

                $map = $newMap;
            }

            foreach ($map as $data) {
                $arrayKey = ArrayHelper::getValue($data, 'key');
                $column = ArrayHelper::getValue($data, 'column');

                if ($value = ArrayHelper::getValue($sort, $arrayKey)) {
                    $orderBy[] = $column . ' ' . $value;
                }
            }
        } else {
            foreach ($sort as $key => $value) {
                $orderBy[] = $key . ' ' . $value;
            }
        }

        if (!$orderBy) {
            return;
        }

        $orderBy = implode(", ", $orderBy);

        $builder->orderBy($orderBy);
    }

    /**
     * Автоматически создает сортировку в зависимости от разрешенных сортировок по правилам в $options и
     * сортировки $sort, которая необходима клиенту.
     * $options - это массив с правилами, по которым будет создана карта с допустимыми сортировками.
     * Могут быть следующие ключи:
     * * key - искать данные о сортировке в ключе key в массиве $sort
     * * alias - алиас в запросе
     * * columns - массив с доступными для сортировки столбцами
     * * class - класс, по которому будет создан массив columns
     * @param BuilderInterface $builder
     * @param array $sort
     * @param array $options
     */
    public static function sort(BuilderInterface &$builder, $sort, $options)
    {
        $orderBy = [];

        foreach ($options as $option) {
            if (ArrayHelper::keyExists('key', $option)) {
                $data = ArrayHelper::getValue($sort, $option['key']);
            } else {
                $data = $sort;
            }

            if (empty($data)) {
                continue;
            }

            $alias = ArrayHelper::getValue($option, 'alias');
            $columns = ArrayHelper::getValue($option, 'columns', []);

            if (ArrayHelper::keyExists('class', $option)) {
                $properties = get_class_vars($option['class']);
                $columns = array_merge($columns, array_keys($properties));
            }

            if (empty($columns)) {
                continue;
            }

            foreach ($data as $item) {
                $column = ArrayHelper::getValue($item, 'column');
                $order = ArrayHelper::getValue($item, 'order', 'ASC');

                if (!in_array($column, $columns)) {
                    continue;
                }

                if ($alias) {
                    $orderBy[] = sprintf("%s.%s %s", $alias, $column, $order);
                } else {
                    $orderBy[] = sprintf("%s %s", $column, $order);
                }
            }
        }

        if (!$orderBy) {
            return;
        }

        $orderBy = implode(", ", $orderBy);
        $builder->orderBy($orderBy);
    }

    /**
     * Метод, с помощью которого создается пагинация.
     * @param BuilderInterface $builder
     * @param int $limit
     * @param int $page
     * @see PaginatorQueryBuilder::getPaginate()
     * @return \stdClass
     */
    public static function paginate(BuilderInterface $builder, $limit, $page)
    {
        $pagination = new PaginatorQueryBuilder([
            'builder' => $builder,
            'limit' => $limit,
            'page' => $page
        ]);

        $page = $pagination->getPaginate();

        return $page;
    }
}