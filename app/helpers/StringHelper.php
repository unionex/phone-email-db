<?php
namespace App\Helpers;

/**
 * Хэлпер, упрощающий работу со строками.
 * Class StringHelper
 * @package App\Helpers
 */
class StringHelper
{
    /**
     * Транслит строки.
     * @param string $string
     * @return string
     */
    public static function transliteration($string)
    {
        $rus = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т',
            'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж',
            'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы',
            'ь', 'э', 'ю', 'я'];

        $lat = ['A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T',
            'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e',
            'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch',
            'y', 'y', 'y', 'e', 'yu', 'ya'];

        return str_replace($rus, $lat, $string);
    }

    /**
     * Возвращает название папки.
     * Пример:
     * ```php
     * echo StringHelper::dirName("Foo/Bar/Baz.txt"); // Foo/Bar
     * ```
     * @param string $path
     * @return string
     */
    public static function dirName($path)
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');

        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        }

        return '';
    }

    /**
     * Возвращает название файла.
     * Пример:
     * ```php
     * $fileName = "app/controllers/ExaminationController.php";
     * echo StringHelper::baseName($fileName); // ExaminationController.php
     * echo StringHelper::baseName($fileName, "Controller.php"); // Examination
     * ```
     * @param string $path
     * @param string $suffix
     * @return string
     */
    public static function baseName($path, $suffix = "")
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }

        $path = rtrim(str_replace('\\', '/', $path), '/\\');

        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    /**
     * Превращает строку типа foo_bar_baz в fooBarBaz.
     * @param string $string
     * @param bool $firstUpper
     * @return null|string|string[]
     */
    public static function camelize($string, $firstUpper = false)
    {
        if ($firstUpper) {
            $string = ucfirst($string);
        }

        return preg_replace_callback(
            '/[-_]([a-z])/',
            function ($match) {
                return strtoupper($match[1]);
            },
            $string
        );
    }

    /**
     * Превращает строку типа fooBarBaz в foo_bar_baz.
     * @param string $string
     * @param string $separator
     * @return null|string|string[]
     */
    public static function underscore($string, $separator = "_")
    {
        return preg_replace_callback(
            '/([a-z])([A-Z])/',
            function ($match) use ($separator) {
                return $match[1] . $separator . strtolower($match[2]);
            },
            $string
        );
    }

    /**
     * Генерирует ключ, пригодный, например, для названия кеша.
     * @param string $prefix префикс.
     * @param mixed ...$args дополнительные данные.
     * @return string
     */
    public static function key($prefix, ...$args)
    {
        $parts = [];

        foreach ($args as $arg) {
            $parts[] = json_encode($arg);
        }

        return $prefix . "." . md5(implode(".", $parts));
    }
}