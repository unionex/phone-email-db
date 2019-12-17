<?php
namespace App\Helpers;

/**
 * Класс, упрощающий работу с файлами.
 * Class FileHelper
 * @package App\Helpers
 */
class FileHelper
{
    /**
     * @var array массив с созданными временными файлами.
     */
    protected static $tempFiles = [];
    /**
     * @var string путь к папке с временными файлами.
     */
    public static $tempDir = APP_DIR . '/temp';

    /**
     * Создает временный файл и возвращает его название с путем к нему.
     * В случае, если создать не удастся, выбросит исключение.
     * @param string|null $fileName название файла. Если null, будет сгенерирован автоматически.
     * @param bool $isDir если равен true, то будет создана папка. В противном случае будет создан файл.
     * @return string
     * @throws \Exception
     */
    public static function tmpFile(string $fileName = null, $isDir = false)
    {
        if (count(static::$tempFiles) == 0) {
            register_shutdown_function(function () {
                $files = scandir(static::$tempDir);
                $files = array_diff($files, [".", ".."]);

                foreach ($files as $file) {
                    static::rm(static::$tempDir . "/" . $file);
                }
            });
        }

        if ($fileName == null) {
            $fileName = static::$tempDir . '/' . md5(rand() . count(static::$tempFiles));
        } else {
            $fileName = static::$tempDir . '/' . $fileName;
        }

        $dir = StringHelper::dirName($fileName);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if ($isDir) {
            $res = mkdir($fileName);
        } else {
            $res = file_put_contents($fileName, "");
        }

        if ($res === false) {
            throw new \Exception("Can not create temporary " . ($isDir ? "dir" : "file"));
        }

        static::$tempFiles[] = $fileName;

        return $fileName;
    }

    /**
     * Удаляет файл, либо директорию. Действует рекурсивно.
     * @param string $src
     */
    public static function rm($src)
    {
        if (!is_dir($src)) {
            unlink($src);
            return;
        }

        $dir = opendir($src);

        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                static::rm($src . '/' . $file);
            }
        }

        closedir($dir);
        rmdir($src);
    }

    /**
     * Конвертирует в base64.
     * @param string $data
     * @return string
     */
    public static function encode($data)
    {
        return base64_encode($data);
    }

    /**
     * Конвертирует из base64.
     * @param string $data
     * @return string
     */
    public static function decode($data)
    {
        return base64_decode($data);
    }
}