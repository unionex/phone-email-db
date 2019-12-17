<?php
namespace App\Cli;

use App\Modules\Examination\Services\Loader as LoaderService;
use App\Modules\Acl\Services\AuthManager;
use Phalcon\Acl\AdapterInterface as AclAdapterInterface;
use Phalcon\Cache\BackendInterface as CacheBackendInterface;
use Phalcon\Db\AdapterInterface as DbAdapterInterface;

/**
 * Дефолтный консольный таск приложения.
 * Class Task
 * @package App\Cli
 * @property-read AclAdapterInterface $acl
 * @property-read AuthManager $auth
 * @property-read DbAdapterInterface $dbBasic
 * @property-read CacheBackendInterface $cache
 */
class Task extends \Phalcon\Cli\Task
{
    /**
     * Список дополнительных сервисов приложения.
     * @var array
     */
    protected $appServices = [
        'cache',
    ];

    public function __get($propertyName)
    {
        if (in_array($propertyName, $this->appServices)) {
            return $this->getDI()->getShared($propertyName);
        }

        return parent::__get($propertyName);
    }

    /**
     * Выводит окно с подтверждением.
     * Если пользователь введет "y" - вернет true, false в противном случае.
     * @param string $question
     * @return bool
     */
    public function confirmation($question)
    {
        echo $question . " [y/N]", PHP_EOL;

        if (strtolower(trim(fgets(STDIN))) !== 'y') {
            return false;
        }

        return true;
    }

    /**
     * Выводит сообщение об ошибке.
     * @param string $errorMessage текст сообщения.
     * @return int
     */
    protected function error(string $errorMessage) : int
    {
        echo \Codedungeon\PHPCliColors\Color::bg_red(1) . 'ERR!';
        echo \Codedungeon\PHPCliColors\Color::normal() . ' ';
        echo $errorMessage;
        echo PHP_EOL;

        return 1;
    }

    /**
     * Выводит сообщение об ошибке.
     * @param string $errorMessage текст сообщения.
     */
    protected function info(string $errorMessage) : void
    {
        echo \Codedungeon\PHPCliColors\Color::bg_cyan(1) . 'INFO!';
        echo \Codedungeon\PHPCliColors\Color::normal() . ' ';
        echo $errorMessage;
        echo PHP_EOL;
    }
}