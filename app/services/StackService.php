<?php
namespace App\Services;

/**
 * Сервис по вставке элементов пачками.
 * Class PhoneService
 * @package App\Services
 */
class StackService
{

    /**
     * @var bool выключить ошибки при дубликатах.
     */
    public $noConflict = true;

    /**
     * @var array массив с данными, которые будут отправлены. Это массив с массивами!
     */
    protected $stacks = [];

    /**
     * @var string название таблицы.
     */
    protected $tableName;

    /**
     * @var array столбцы для вставки. Первый элемент считается как PK.
     */
    protected $columns = [];

    /**
     * StackService constructor.
     * @param string $tableName название таблицы.
     * @param array $columns столбцы для вставки.
     */
    public function __construct(string $tableName, array $columns)
    {
        $this->tableName = $tableName;
        $this->columns = $columns;
    }

    /**
     * Возвращает адаптер БД.
     * @return \Phalcon\Db\Adapter
     */
    protected function getDb() : \Phalcon\Db\Adapter
    {
        return \Phalcon\Di::getDefault()->getShared("db");
    }

    /**
     * Возвращает кол-во стаков, готовых к отправке.
     * @return int
     */
    public function count() : int
    {
        return count($this->stacks);
    }

    /**
     * Очищает стаки и готовится к отправке пачки.
     */
    public function begin() : void
    {
        $this->stacks = [];
    }

    /**
     * Добавляет стак.
     * @param array $stack стак.
     */
    public function add(array $stack)
    {
        $this->stacks[] = $stack;
    }

    /**
     * Отправляет пачку.
     */
    public function end() : void
    {
        if ($this->count() == 0) {
            return;
        }

        $tableName = $this->tableName;
        $columns = implode(', ', $this->columns) ;

        $values = [];

        foreach ($this->stacks as $stack) {
            $row = [];

            foreach ($stack as $item) {
                $row[] = $item;
            }

            $values[] = "'" . implode("', '", $row) . "'";
        }


        if ($this->noConflict) {
            $query = sprintf(
                "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO NOTHING",
                $tableName,
                $columns,
                implode("), (", $values),
                $this->columns[0]
            );
        } else {
            $query = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $tableName,
                $columns,
                implode("), (", $values)
            );
        }
        print_r([base64_encode($query)]);
        $this->getDb()->execute($query);
    }
}