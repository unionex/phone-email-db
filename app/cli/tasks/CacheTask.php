<?php
namespace App\Cli\Tasks;

use App\Cli\Task;
use Phalcon\Cache\Backend as BackendCache;

class CacheTask extends Task
{
    /**
     * @return BackendCache
     */
    protected function getCacheService()
    {
        return $this->getDI()->getShared('cache');
    }

    /**
     * Возвращает список ключей кеша
     *
     * Пример использования:
     * php cli cache list
     */
    public function listAction()
    {
        $cache = $this->getCacheService();

        foreach ($cache->queryKeys() as $queryKey) {
            echo $queryKey . PHP_EOL;
        }
    }

    /**
     * Удаляет все элементы кеша
     *
     * Пример использования:
     * php cli cache flush
     */
    public function flushAction()
    {
        $cache = $this->getCacheService();

        foreach ($cache->queryKeys() as $queryKey) {
            $cache->delete($queryKey);
        }
    }
}