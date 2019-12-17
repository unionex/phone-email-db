<?php
namespace App\Helpers;

use Closure;
use Phalcon\Mvc\Model\Resultset;

/**
 * Хэлпер, который помогает работать с массивами, объектами и некоторыми коллекциями Phalcon.
 * Class ArrayHelper
 * @package App\Helpers
 */
class ArrayHelper
{
    /**
     * Применяет анонимную функцию к каждому элементу массива $array, и, если он возвращает true, то этот
     * элемент тоже возвращается.
     * @param array $array массив.
     * @param \Closure $closure анонимная функция.
     * @return array
     */
    public static function filter($array, $closure)
    {
        $result = [];

        foreach ($array as $item) {
            if ($closure($item) == true) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Метод, с помощью которого создается пагинация для массивов.
     * @param array $array
     * @param int $limit
     * @param int $page
     * @return \stdClass
     */
    public static function pagination($array, $limit, $page)
    {
        if ($array instanceof Resultset\Simple) {
            $array = static::toArray($array, [], false);
        }

        $paginator = new \Phalcon\Paginator\Adapter\NativeArray([
            'data' => $array,
            'limit' => $limit,
            'page' => $page
        ]);

        return $paginator->getPaginate();
    }

    /**
     * Сортирует массив.
     * @param array $array
     * @param string|\Closure|array $key
     * @param int|array $direction
     * @param int|array $sortFlag
     * @throws \Exception if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public static function multiSort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        $keys = is_array($key) ? $key : [$key];

        if (empty($keys) || empty($array)) {
            return;
        }

        $n = count($keys);

        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new \Exception('The length of $direction parameter must be the same as that of $keys.');
        }

        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new \Exception('The length of $sortFlag parameter must be the same as that of $keys.');
        }

        $args = [];

        foreach ($keys as $i => $key) {
            $flag = $sortFlag[$i];
            $args[] = static::column($array, $key);
            $args[] = $direction[$i];
            $args[] = $flag;
        }

        // This fix is used for cases when main sorting specified by columns has equal values
        // Without it it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;
        $args[] = &$array;

        call_user_func_array('array_multisort', $args);
    }

    /**
     * Мерджит два или более массивов.
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);

        while (!empty($args)) {
            foreach ((array) array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }
    /**
     * Переводит объект в массив.
     * @param object|array|string $object
     * @param array $properties
     * @param bool $recursive
     * @return array
     */
    public static function toArray($object, $properties = [], $recursive = true)
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = static::toArray($value, $properties, true);
                    }
                }
            }

            return $object;
        } elseif ($object instanceof Resultset\Simple) {
            $result = [];

            foreach ($object as $item) {
                $result[] = $item;
            }

            return $recursive ? static::toArray($result, $properties) : $result;
        } elseif (is_object($object)) {
            if (!empty($properties)) {
                $className = get_class($object);

                if (!empty($properties[$className])) {
                    $result = [];

                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = static::getValue($object, $name);
                        }
                    }

                    return $recursive ? static::toArray($result, $properties) : $result;
                }
            }

            $result = [];

            foreach ($object as $key => $value) {
                $result[$key] = $value;
            }

            return $recursive ? static::toArray($result, $properties) : $result;
        }

        return [$object];
    }

    /**
     * Получает значение массива или объекта по ключу $key, в котором в качестве разделителей ключей могут
     * использоваться ".", либо $key может быть массивом ключей.
     * В случае, если ключ не существует в массиве, будет возвращен $default.
     * Пример:
     * ```php
     * $array = [
     *     'key' => [
     *         'foo' => 'bar'
     *     ]
     * ];
     * echo ArrayHelper::getValue($array, "key.foo"); // bar
     * ```
     * Кроме того, $key может быть коллбеком:
     * ```php
     * $array = [
     *     'key' => [
     *         'foo' => 'bar'
     *     ]
     * ];
     * echo ArrayHelper::getValue($array, function ($item) {
     *     return $item['key']['foo'];
     * }); // bar
     * ```
     * @param array|object $array
     * @param array|string|\Closure $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);

            foreach ($key as $keyItem) {
                $array = static::getValue($array, $keyItem, $default);
            }

            $key = $lastKey;
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_array($array) || $array instanceof Resultset) {
            if ($array instanceof Resultset && is_numeric($key)) {
                $key = (int) $key;
            }

            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        } elseif (is_object($array)) {
            return $array->$key;
        }

        return $default;
    }

    /**
     * Устанавливает значение массива, работает по такому же принципу, что и getValue.
     * @param array $array
     * @param array|string $path
     * @param mixed $value
     * @see ArrayHelper::getValue()
     */
    public static function setValue(&$array, $path, $value)
    {
        if ($path === null) {
            $array = $value;
            return;
        }

        $keys = is_array($path) ? $path : explode('.', $path);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key])) {
                $array[$key] = [];
            }

            if (!is_array($array[$key])) {
                $array[$key] = [$array[$key]];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Принимает массив с массивами и возвращает значение в $key каждого из них:
     * ```php
     * $array = [
     *     ['id' => 15, 'name' => 'John'],
     *     ['id' => 27, 'name' => 'Erik'],
     *     ['id' => 34, 'name' => 'Veronica']
     * ];
     * $result = ArrayHelper::column($array, 'id'); // [15, 27, 34]
     * ```
     * @param array $array
     * @param array|string $key
     * @param bool $keepKeys
     * @return mixed
     */
    public static function column($array, $key, $keepKeys = false)
    {
        $result = [];

        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::getValue($element, $key);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::getValue($element, $key);
            }
        }

        return $result;
    }

    /**
     * Принимает массив с массивами и возвращает новый массив, ключи которого - значение в ключе соответствующего
     * элемента массива.
     * Пример:
     * ```php
     * $array = [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Erik'],
     *     ['id' => 3, 'name' => 'Veronica']
     * ];
     * $result = ArrayHelper::column($array, 'id'); // [15 => ['id' => 15, 'name' => 'John'], ...]
     * ```
     * @param array $array
     * @param array|string|\Closure $key
     * @return array
     */
    public static function index($array, $key)
    {
        $result = [];

        foreach ($array as $item) {
            $index = static::getValue($item, $key);

            $result[$index] = $item;
        }

        return $result;
    }

    /**
     * Проверяет существование ключа $key в массиве $array.
     * @param string $key
     * @param array $array
     * @param bool $caseSensitive
     * @return bool
     */
    public static function keyExists($key, $array, $caseSensitive = true)
    {
        if (!is_array($array)) {
            $array = static::toArray($array);
        }

        if ($caseSensitive) {
            return isset($array[$key]) || array_key_exists($key, $array);
        }

        foreach (array_keys($array) as $k) {
            if (strcasecmp($key, $k) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Устанавливает значения свойств объекта $object значениями из $array.
     * @param object $object
     * @param array $array
     * @param array $map
     */
    public static function configure(&$object, $array, $map = [])
    {
        if ($map) {
            foreach ($array as $key => $value) {
                if (!isset($map[$key])) {
                    continue;
                }

                $property = $map[$key];

                if ($property instanceof \Closure) {
                    $property($object);
                } else {
                    $object->$property = $value;
                }
            }
        } else {
            foreach ($array as $property => $value) {
                $object->$property = $value;
            }
        }
    }

    /**
     * Проверяет, ассоциативный ли массив.
     * @param array $array
     * @param bool $allStrings
     * @return bool
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, нумерованный ли массив.
     * @param array $array
     * @param bool $consecutive
     * @return bool
     */
    public static function isIndexed($array, $consecutive = false)
    {
        if (!is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return true;
        }

        if ($consecutive) {
            return array_keys($array) === range(0, count($array) - 1);
        }

        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }
}