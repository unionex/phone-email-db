<?php
namespace App\Controllers;
use App\Modules\Examination\Services\Loader as LoaderService;
use App\Modules\Logs\Services\LoggerService;
use App\Modules\Acl\Services\AuthManager;
use App\Modules\Exchange\Services\ExchangeService;
use App\Services\SettingsManager;
use App\Modules\Exchange\Services\UpdateService;
use App\Services\VersionManager;
use Phalcon\Acl\AdapterInterface as AclAdapterInterface;
use Phalcon\Cache\BackendInterface as CacheBackendInterface;
use Phalcon\Db\AdapterInterface as DbAdapterInterface;
use Phalcon\Di\Injectable;
use Phalcon\Logger\AdapterInterface;

/**
 * Дефолтный контроллер приложения, от которого наследуются все остальные.
 * Class ControllerBase
 * @package App\Controllers
 * @property-read AclAdapterInterface $acl
 * @property-read AuthManager $auth
 * @property-read ExchangeService $exchange
 * @property-read DbAdapterInterface $dbBasic
 * @property-read SettingsManager $settings
 * @property-read LoggerService $updateLog
 * @property-read LoggerService $sysLog
 * @property-read AdapterInterface $logger
 * @property-read CacheBackendInterface $cache
 * @property-read VersionManager $version
 * @property-read UpdateService $update
 * @property-read LoaderService $testLoader
 */
class ControllerBase extends Injectable
{
    /**
     * Список дополнительных сервисов приложения.
     * @var array
     */
    protected $appServices = [
        'acl',
        'auth',
        'exchange',
        'dbBasic',
        'settings',
        'updateLog',
        'sysLog',
        'cache',
        'logger',
        'version',
        'update',
        'testLoader'
    ];

    public function __get($propertyName)
    {
        if (in_array($propertyName, $this->appServices)) {
            return $this->getDI()->getShared($propertyName);
        }

        return parent::__get($propertyName);
    }
}