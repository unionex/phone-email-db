<?php
namespace App\Cli\Tasks;

use App\Helpers\ArrayHelper;
use App\Helpers\FilterHelper;

class ImportTask extends \App\Cli\Task
{
    /**
     * @var int $microTime
     */
    protected $microTime;

    protected function begin()
    {
        $this->microTime = microtime(true);
    }

    protected function end()
    {
        echo "Script Time - " . (microtime(true) - $this->microTime) . PHP_EOL;
    }

    /**
     * Импортирует телефоны.
     *
     * import phone <название_файла>
     *
     * @param $params
     * @return int
     */
    public function phoneAction($params)
    {
        $this->begin();

        $file = ArrayHelper::getValue($params, 0, APP_DIR . '/exchange/load/mobile.csv');

        echo "Using file '{$file}'" . PHP_EOL;

        if (!file_exists($file)) {
            return $this->error("File {$file} not found");
        }

        if (($list = file_get_contents($file)) == false) {
            return $this->error("Cannot open file {$file}");
        }

        $list = explode(PHP_EOL, $list);

        $stackService = new \App\Services\StackService("phones", ["number"]);

        $stackService->begin();

        foreach ($list as $i => $phoneNumber) {
            if (empty($phoneNumber)) {
                continue;
            }

            $phoneNumber = FilterHelper::trim($phoneNumber);
            $phoneNumber = mb_strtoupper($phoneNumber);

            $stackService->add([$phoneNumber]);

            // пачки отправляем по 10000
            if ($stackService->count() == 10000) {
                $stackService->end();
                $stackService->begin();

                $this->end();
                $this->begin();
            }
        }

        $stackService->end();

        echo count($list) . " processed." . PHP_EOL;

        if (unlink($file)) {
            echo "ok." . PHP_EOL;
        } else {
            echo "error." . PHP_EOL;
        }

        $this->end();

        return 0;
    }


    /**
     * Импортирует эл. почту.
     *
     * import email <название_файла>
     *
     * @param $params
     * @return int
     */
    public function emailAction($params)
    {
        $file = ArrayHelper::getValue($params, 0, APP_DIR . '/exchange/load/email.csv');

        echo "Using file '{$file}'" . PHP_EOL;

        if (!file_exists($file)) {
            return $this->error("File {$file} not found");
        }

        if (($list = file_get_contents($file)) == false) {
            return $this->error("Cannot open file {$file}");
        }

        $list = explode(PHP_EOL, $list);

        $stackService = new \App\Services\StackService("emails", ["email"]);
        $stackService->noConflict = false;

        $stackService->begin();

        foreach ($list as $i => $email) {
            if (empty($email)) {
                continue;
            }

            $email = FilterHelper::trim($email);
            $email = mb_strtoupper($email);

            $stackService->add([$email]);

            // пачки отправляем по 10000
            if ($stackService->count() == 10000) {
                $stackService->end();
                $stackService->begin();

                $this->end();
                $this->begin();
            }
        }

        $stackService->end();

        echo count($list) . " processed." . PHP_EOL;

        if (unlink($file)) {
            echo "ok." . PHP_EOL;
        } else {
            echo "error." . PHP_EOL;
        }

        $this->end();

        return 0;
    }
}