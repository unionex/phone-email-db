<?php
namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Хэлпер, упрощающий работу с PhpSpreadsheet
 * Class OfficeHelper
 * @package App\Helpers
 */
class OfficeHelper
{
    /**
     * Достает данные из рабочего листа $worksheet и конвертирует их в нумерованный массив.
     * Если $firstRowHasColumnNames = true, то массив будет ассоциативным. С помощью $columnMap можно задавать другие
     * названия ключам.
     * @param Worksheet $worksheet
     * @param bool $firstRowHasColumnNames
     * @param array|null $columnMap
     * @return array
     */
    public static function toArray(Worksheet $worksheet, $firstRowHasColumnNames = false, $columnMap = null)
    {
        $array = $worksheet->toArray();

        if ($firstRowHasColumnNames) {
            $columns = array_shift($array);

            if ($columnMap) {
                foreach ($columns as &$column) {
                    $column = $columnMap[$column];
                }

                unset($column);
            }
        } else {
            $columns = $columnMap;
        }

        if (!$columns) {
            return $array;
        }

        $result = [];

        foreach ($array as $item) {
            $row = [];

            foreach ($item as $key => $value) {
                $row[$columns[$key]] = $value;
            }

            $result[] = $row;
        }

        return $result;
    }
}