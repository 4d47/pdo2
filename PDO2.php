<?php

/**
 * Change PDO to have better constructing defaults
 * and to include some executing helpers.
 */
class PDO2 extends PDO
{

    /**
     * Construct PDO with healthier defaults:
     *
     *   - MYSQL_INIT_COMMAND to "SET NAMES 'UTF8'" if charset param not in DSN
     *   - ATTR_ERRMODE to ERRMODE_EXCEPTION
     *   - ATTR_DEFAULT_FETCH_MODE to FETCH_OBJ
     */
    public function __construct($dsn, $username = '', $password = '', array $driver_options = array())
    {
        if (preg_match('/^mysql/i', $dsn) && !preg_match('/;charset=/', $dsn) && !isset($driver_options[PDO::MYSQL_ATTR_INIT_COMMAND])) {
            $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
        }
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    }

    /**
     * Prepare and execute a single statement shortcut.
     *
     * @param string $statement
     * @param array $input_parameters
     * @return PDOStatement
     */
    public function execute($statement, array $input_parameters = array())
    {
        $stm = $this->prepare($statement);
        $stm->execute($input_parameters);
        return $stm;
    }

    /**
     * Build and `execute()` a simple `SELECT` statement.
     * Parameters are polymorphic so params be be skipped.
     *
     *   $table
     *   $table, $params:array
     *   $table, $extra:string
     *   $table, $extra:string, $values:array
     *   $table, $params:array, $extra:string
     *   $table, $params:array, $extra:string, values:array
     *
     * @param string $table table name may optionally contain columns,
     *        eg. "a, b,c from table".
     * @param array $params assoc where parameters
     * @param string $extra optional sql after where
     * @param array $values values in extra string
     * @return PDOStatement
     */
    public function select($table, $params = array(), $extra = '', $values = array())
    {
        switch(func_num_args()) {
        case 2:
            if (is_string($params)) {
                $extra = $params;
                $params = array();
            }
            break;
        case 3:
            if (is_string($params)) {
                $values = $extra;
                $extra = $params;
                $params = array();
            }
            break;
        }
        if (!preg_match('/ FROM /i', $table))
            $table = "* FROM $table";
        $vals = array();
        $where = $this->where($params, $vals);
        $values = array_merge($vals, $values);
        return $this->execute("SELECT $table $where $extra", $values);
    }

    /**
     * Build and `execute()` a simple `INSERT` statement.
     *
     * @param string $table
     * @param array $params assoc of column => value to insert
     * @return PDOStatement
     */
    public function insert($table, array $params)
    {
        $columns = implode(', ', array_keys($params));
        $values = implode(', ', array_fill(0, count($params), '?'));
        return $this->execute("INSERT INTO $table ($columns) VALUES ($values)", array_values($params));
    }

    /**
     * Build and `execute()` a simple `UPDATE` statement.
     *
     * @param string $table
     * @param array $params assoc of column => value to update
     * @param array $where assoc of where conditions
     * @return PDOStatement
     */
    public function update($table, array $params, array $where)
    {
        $set = implode(', ', array_map(function ($e) { return "$e = ?"; }, array_keys($params)));
        $values = array_values($params);
        $where = $this->where($where, $values);
        return $this->execute("UPDATE $table SET $set $where", $values);
    }

    /**
     * Build and `execute()` a simple `DELETE` statement.
     *
     * @param string $table
     * @param array $params assoc of where conditions
     * @return PDOStatement
     */
    public function delete($table, array $params)
    {
        $values = array();
        $where = $this->where($params, $values);
        return $this->execute("DELETE FROM $table $where", $values);
    }

    /**
     * Build and `execute()` a simple `SELECT COUNT(*)` statement.
     *
     * @param string $table
     * @param array $params
     * @return int
     */
    public function count($table, array $params = null)
    {
        $values = array();
        $where = $this->where($params, $values);
        return $this->execute("SELECT COUNT(*) FROM $table $where", $values)->fetchColumn();
    }

    /**
     * Build `WHERE` expression from $params and append $values.
     *
     * @param array $params
     * @param array $values
     * @return string
     */
    public function where(array $params = null, array &$values)
    {
        $expr = $this->expr($params, $values);
        return $expr ? "WHERE $expr" : '';
    }

    /**
     * Build expression from $params and append $values.
     *
     * @param array $params
     * @param array $values
     */
    public function expr(array $params = null, array &$values)
    {
        if (empty($params)) {
            return '';
        } else if (is_array($params) and $this->isAssoc($params)) {
            $clauses = array();
            foreach ($params as $name => $param) {
                if (is_numeric($name)) {
                    if ($param) {
                        $clauses[] = '(' . $this->expr($param, $values) . ')';
                    }
                } else {
                    $clauses[] = strpos($name, ' ') === false ? "$name = ?" : $name;
                    $values[] = $param;
                }
            }
            return implode(' AND ', $clauses);
        } else {
            $clauses = array();
            foreach ($params as $param) {
                if ($param) {
                    $clauses[] = '(' . $this->expr($param, $values) . ')';
                }
            }
            return implode(' OR ', $clauses);
        }
    }

    private function isAssoc($array)
    {
        return 0 !== count(array_diff_key($array, array_keys(array_keys($array))));
    }
}
?>
