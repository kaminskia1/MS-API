<?php

// Database helper class

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Db implements Helper
{

    /**
     * @breif The main mysqli database
     */
    private $db;

    /**
     * Db constructor.
     */
    public function __construct()
    {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_SCHEMA, DB_PORT);
        if ($this->db->connect_error) {
            Errors::Internal();
        }
    }

    /**
     * Initiate the Database class
     *
     * @return Db
     */
    static public function i(): Db
    {
        $Db = new Db();
        // ---

        // ---
        return $Db;
    }

    /**
     * Preprocessor for calling one of the predefined queries
     *
     * @return Query
     */
    public function staticQuery(): Query
    {
        return new Query();
    }


    /**
     * Select data from the database
     *
     * @param $column string
     * @param $table  string
     * @param $where  string/array
     * @param $order  string
     *
     * @return Select
     */
    public function select($column, $table, $where = null, $order = null): Select
    {
        // Start statement
        $query = "SELECT {$column} FROM {$table}";

        // Check if where is string or array
        if (is_array($where)) {
            for ($i = 0; $i < count($where); $i++) {
                if ($i == 0) {
                    if ($where != null && $where != '') {
                        $query .= " WHERE {$where}";
                    }
                } else {
                    if ($where != null && $where != '') {
                        $query .= " AND {$where}";
                    }
                }
            }
        } else {
            if ($where != null && $where != '') {
                $query .= " WHERE {$where}";
            }
        }

        // Check if order by is set
        if ($order != null) {
            $query .= " {$order}";
        }
        return new Select($this, $query, $this->db->query($query));

    }

    /**
     * Insert 2-dimensional data into the database
     *
     * @info if header is null, use top of 2-d array as names, if set, use all data as data
     *
     * @param $table  string
     * @param $data   array [1-D/2-D]
     * @param $header array [1-D]
     *
     * @return bool
     */
    public function insert($table, $data, $header = null): bool
    {
        // Check if header is set, create it if not
        if ($header == null) {
            if (count($data) < 2) {
                throw new \UnderflowException('No header/data to insert!', 'FATAL');
            }
            $header = $data[0];
            $noHeader = true;
        } else {
            $noHeader = false;
        }

        // Check that header matches column lengths
        for ($i = 1; $i < count($data); $i++) {
            if (count($header) != count($data[$i])) {
                throw new \Error('Header-Data length mismatch at data row ' . $i . '!', 'FATAL');
            }
        }

        // Start string construction
        $str = '';
        for ($i = 0; $i < count($header); $i++) {
            $str .= "`{$header[$i]}`,";
        }
        $str = substr($str, 0, strlen($str) - 1);
        $query = "INSERT INTO `{$table}` ({$str}) VALUES";

        if ($noHeader) {
            $d = 1;
        } else {
            $d = 0;
        }
        $start = false;
        while ($d < count($data)) {
            if ($start) {
                $str = ",";
            } else {
                $str = '';
            }
            $start = true;
            $str .= ' (';
            for ($i = 0; $i < count($data[$d]); $i++) {
                $str .= "'{$data[$d][$i]}',";
            }
            $str = substr($str, 0, strlen($str) - 1) . ')';
            $query .= $str;
            $d++;
        }


        if (!SANDBOX_MODE) {
            return (bool)($this->db->query($query . ";"));
        }

        API::i($query, 200)->output();

        // Pointless, but allows us to declare function return type as bool
        return false;
    }

    /**
     * Insert 1-dimensional data into the database
     *
     * @param $table  string
     * @param $values array/object
     *
     * @return bool
     */
    public function insertSingle($table, $values): bool
    {
        $query = "INSERT INTO `{$table}` (";

        // Convert possible object into array
        if (is_object($values)) {
            $values = (array)$values;
        }

        $names = [];
        $vals = [];
        // Separate keys and values
        foreach ($values as $k => $v) {
            array_push($names, $k);
            array_push($vals, $v);
        }
        if (count($names) == count($vals)) {
            for ($i = 0; $i < count($names); $i++) {
                if ($i != 0) {
                    $query .= ",";
                }
                $query .= "`{$names[$i]}`";
            }
            $query .= ") VALUES (";

            for ($i = 0; $i < count($vals); $i++) {
                if ($i != 0) {
                    $query .= ",";
                }
                $query .= "'{$vals[$i]}'";
            }

            $query .= ");";

            if (!SANDBOX_MODE) {
                return (bool)$this->db->query($query);
            }

            API::i($query, 200)->output();

        } else {
            return false;
        }
    }


    /**
     * Update data in the database
     *
     * @param $table string
     * @param $set   string
     * @param $where string
     *
     * @return bool
     */
    public function update($table, $set, $where = null): bool
    {
        $query = "UPDATE `{$table}` SET {$set}";
        if ($where != null) {
            $query .= " WHERE {$where}";
        }

        if (!SANDBOX_MODE) {
            return (bool)$this->db->query($query);
        }

        API::i($query, 200)->output();

        return false;
    }

    /**
     * Delete row(s) from a table
     *
     * @param      $table
     * @param null $where
     *
     * @return bool
     */
    public function delete($table, $where = null): bool
    {
        $query = "DELETE FROM `{$table}`";
        if ($where != null) {
            $query .= " WHERE " . $where;
        }

        if (!SANDBOX_MODE) {
            return (bool)$this->db->query($query);
        }

        API::i($query, 200)->output();

        return false;
    }

    /**
     * Execute a provided query
     *
     * @param $query
     *
     * @return mixed
     */
    public function execute($query)
    {
        return $this->db->query($query);
    }

}