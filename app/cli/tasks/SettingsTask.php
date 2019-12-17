<?php
namespace App\Cli\Tasks;

use App\Cli\Task;
use App\Services\SettingsManager;

class SettingsTask extends Task
{
    /**
     * Проверяет, существует ли параметр
     *
     * Пример использования:
     * php cli settings has <название_параметра>
     *
     * @param array $params
     */
    public function hasAction($params)
    {
        /** @var SettingsManager $manager */
        $manager = $this->getDI()->getShared("settings");

        echo $manager->has($params[0]) ? "true" : "false";
    }

    /**
     * Устанавливает значение параметра
     *
     * Пример использования:
     * php cli settings set <название_параметра> <значение>
     *
     * @param array $params
     */
    public function setAction($params)
    {
        /** @var SettingsManager $manager */
        $manager = $this->getDI()->getShared("settings");

        $manager->set($params[0], $params[1]);
    }

    /**
     * Возвращает значение параметра
     *
     * Пример использования
     * php cli settings get <название_параметра>
     *
     * @param array $params
     */
    public function getAction($params)
    {
        /** @var SettingsManager $manager */
        $manager = $this->getDI()->getShared("settings");

        var_dump($manager->get($params[0]));
    }

    /**
     * Проверяет обновления.
     *
     * Пример использования:
     * php cli settings update
     *
     * @throws \App\Modules\Exchange\Exceptions\CommercialException
     * @throws \App\Modules\Exchange\Exceptions\HttpException
     * @throws \App\Modules\Exchange\Exceptions\ServiceUnavailableException
     */
    public function updateAction()
    {
        $info = $this->exchange->request("get", "/update/version", [
            'ignoreVersionRequirements' => true
        ]);
        $upToDate = true;
        $needUpdate = [];

        foreach ($info as $appPart => $partInfo) {
            $currentIndex = $this->version->getVersionIndex($partInfo['current']);
            $latestIndex = $this->version->getVersionIndex($partInfo['latest']);

            echo $appPart . PHP_EOL;
            echo "* current: " . $partInfo['current'] . " (" . $currentIndex . ")" . PHP_EOL;
            echo "* latest: " . $partInfo['latest'] . " (" . $latestIndex . ")" . PHP_EOL;

            if ($currentIndex < $latestIndex) {
                $needUpdate[] = $appPart;
                $upToDate = false;
            }
        }

        if ($upToDate) {
            echo "Already up-to-date" . PHP_EOL;
            return 0;
        }

        if ($this->confirmation("Update available. Update?")) {
            $this->update->update($needUpdate);
        }

        return 0;
    }
}