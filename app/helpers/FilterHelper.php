<?php
namespace App\Helpers;

use Phalcon\Filter;

/**
 * Класс, упрощающий работу с фильтрацией данных. Использует стандартный фильтр Phalcon.
 * Class FilterHelper
 * @package App\Helpers
 */
class FilterHelper
{
    /**
     * @var Filter объект фильтра.
     */
    protected static $instance;

    /**
     * Создает объект фильтра.
     * @return Filter
     */
    protected static function createInstance()
    {
        $filter = new Filter();

        $filter->add('bool', function ($value) {
            return $value == 0 ? false : true;
        });

        $filter->add('phone', function ($value) {
            $value = preg_replace(
                '/([\+]?\d+).*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{2}).?(\d{2}).*/',
                '$1$2$3$4$5',
                $value
            );

            return $value;
        });

        return $filter;
    }

    /**
     * Возвращает объект фильтра. Если он не создан, то создаст его.
     * @return Filter
     */
    protected static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = static::createInstance();
        }

        return static::$instance;
    }

    /**
     * Фильтрует $value с помощью фильтров (-а) $filters.
     * Стандартный компонент фильтрации из null может сделать пустую строку, что в некоторых случаях не нужно, поэтому
     * если $strictMode = false и $value = null, то будет возвращен null.
     * @param mixed $value
     * @param string|array $filters
     * @param bool $strictMode
     * @return mixed
     */
    public static function filter($value, $filters, $strictMode = false)
    {
        if ($strictMode === false && is_null($value)) {
            return $value;
        }

        if (!is_array($filters)) {
            $filters = [$filters];
        }

        $instance = static::getInstance();

        foreach ($filters as $filter) {
            $value = $instance->sanitize($value, $filter);
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function email($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_EMAIL, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function absInt($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_ABSINT, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function int($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_INT, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function intCast($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_INT_CAST, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function string($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_STRING, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function float($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_FLOAT, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function floatCast($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_FLOAT_CAST, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function alphaNum($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_ALPHANUM, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function trim($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_TRIM, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function stripTags($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_STRIPTAGS, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function lower($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_LOWER, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function upper($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_UPPER, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function url($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_URL, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function specialChars($value, $strictMode = false)
    {
        return static::filter($value, Filter::FILTER_SPECIAL_CHARS, $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function phone($value, $strictMode = false)
    {
        return static::filter($value, 'phone', $strictMode);
    }

    /**
     * @param mixed $value
     * @param bool $strictMode
     * @return mixed
     */
    public static function bool($value, $strictMode = false)
    {
        return static::filter($value, 'bool', $strictMode);
    }
}