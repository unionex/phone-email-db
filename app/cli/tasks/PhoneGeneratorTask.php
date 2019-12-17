<?php
namespace App\Cli\Tasks;

class PhoneGeneratorTask extends \App\Cli\Task
{
    public function mainAction()
    {
        $current = 79631111111;
        $count = 6500000;

        $collection = [];

        for ($i = 0; $i < $count; $i++) {
            $collection[] = '+' . ($current + $i);

            if ($i % 500 == 0) {
                if ($collection) {
                    $values = '\'' . implode('\'), (\'', $collection) . '\'';
                    $sql = "insert into phones (\"number\") values ($values)";
                    $this->db->query($sql);

                    $collection = [];
                }

                echo "$i / $count \n";
            }
        }

        if ($collection) {
            $values = '"' . implode('", "', $collection) . '"';
            $sql = "insert into phones (\"number\") values ($values)";
            $this->db->query($sql);
        }
    }
}