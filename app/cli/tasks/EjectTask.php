<?php
namespace App\Cli\Tasks;

use App\Helpers\ArrayHelper;
use App\Helpers\FilterHelper;

class EjectTask extends \App\Cli\Task
{
    /**
     * Импортирует телефоны.
     *
     * import-eject phone <название_файла>
     *
     * @param $params
     * @return int
     */
    public function phoneAction($params)
    {
        $file = ArrayHelper::getValue($params, 0, APP_DIR . '/exchange/eject/mobile.csv');

        echo "Using file '{$file}'" . PHP_EOL;

        if (!file_exists($file)) {
            return $this->error("File {$file} not found");
        }

        if (($list = file_get_contents($file)) == false) {
            return $this->error("Cannot open file {$file}");
        }

        $list = explode(PHP_EOL, $list);

        $statAll = 0;
        $statNotFound = 0;
        $statDelete = 0;

        foreach ($list as $phoneNumber) {
            if (empty($phoneNumber)) {
                continue;
            }

            $phoneNumber = FilterHelper::trim($phoneNumber);

            $statAll ++;

            echo "Processing {$phoneNumber}" . PHP_EOL;

            $phone = \App\Models\Phone::findFirst([
                "number = :number:",
                "bind" => [
                    "number" => $phoneNumber
                ]
            ]);

            if (!$phone) {
                $statNotFound ++;
                continue;
            }

            if (!$phone->delete()) {
                $this->error("Could not delete phone");

                foreach ($phone->getMessages() as $message) {
                    $this->error($message);
                }

                return 1;
            }

            $statDelete ++;
        }

        echo "Done. Total {$statAll}, of which {$statNotFound} were not found" . PHP_EOL;
        echo "{$statDelete} deleted" . PHP_EOL;

        echo "Removing file... ";

        if (unlink($file)) {
            echo "ok." . PHP_EOL;
        } else {
            echo "error." . PHP_EOL;
        }

        return 0;
    }
    /**
     * Импортирует эл. почту.
     *
     * import-eject email <название_файла>
     *
     * @param $params
     * @return int
     */
    public function emailAction($params)
    {
        $file = ArrayHelper::getValue($params, 0, APP_DIR . '/exchange/eject/email.csv');

        echo "Using file '{$file}'" . PHP_EOL;

        if (!file_exists($file)) {
            return $this->error("File {$file} not found");
        }

        if (($list = file_get_contents($file)) == false) {
            return $this->error("Cannot open file {$file}");
        }

        $list = explode(PHP_EOL, $list);

        $statAll = 0;
        $statNotFound = 0;
        $statDelete = 0;

        foreach ($list as $email) {
            if (empty($email)) {
                continue;
            }

            $email = FilterHelper::trim($email);

            $statAll ++;

            echo "Processing {$email}" . PHP_EOL;

            $emailInstance = \App\Models\Email::findFirst([
                "email = :email:",
                "bind" => [
                    "email" => $email
                ]
            ]);

            if (!$emailInstance) {
                $statNotFound ++;
                continue;
            }

            if (!$emailInstance->delete()) {
                $this->error("Could not delete e-mail");

                foreach ($emailInstance->getMessages() as $message) {
                    $this->error($message);
                }

                return 1;
            }

            $statDelete ++;
        }

        echo "Done. Total {$statAll}, of which {$statNotFound} were missed" . PHP_EOL;
        echo "{$statDelete} deleted" . PHP_EOL;

        echo "Removing file... ";

        if (unlink($file)) {
            echo "ok." . PHP_EOL;
        } else {
            echo "error." . PHP_EOL;
        }

        return 0;
    }
}