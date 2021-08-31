<?php

// Database Select subclass

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}


class Select extends Db
{

    private $db;

    private $query;

    private $result;

    /**
     * Select constructor.
     *
     * @param $db
     * @param $query
     * @param $result
     *
     * @return void
     */
    public function __construct($db, $query, $result)
    {
        parent::__construct();
        $this->db = $db;
        $this->query = $query;
        $this->result = $result;
    }

    /**
     * Retrieve the Query
     *
     * @return string
     */
    public function __toString()
    {
        return $this->query;
    }

    /**
     * Retrieve the first item, of the first row
     *
     * $return string
     */
    public function first()
    {
        if (@$this->result == null) {
            return null;
        } else {
            while ($row = $this->result->fetch_assoc()) {
                foreach ($row as $item => $val) {
                    return (string)$val;
                }
            }
        }
    }

    /**
     * Gets mysql result in table form
     *
     * @info Returns array of objects
     * @return array
     */
    public function table()
    {
        if (@$this->result == null) {
            return null;
        } else {
            $res = (array)[];
            while ($row = $this->result->fetch_assoc()) {
                $i = (object)[];
                foreach ($row as $item => $val) {
                    $i->$item = $val;
                }
                array_push($res, $i);
            }
            return $res;
        }
    }

    public function row()
    {
        if (@$this->result == null) {
            return null;
        } else {
            while ($row = $this->result->fetch_assoc()) {
                return $row;
            }
        }
    }

    /**
     * Get number of rows
     *
     * @return int
     */
    public function count()
    {
        if (@gettype($this->result->num_rows) == null) {
            return 0;
        }
        return @$this->result->num_rows;
    }

    /**
     * Get raw mysql data
     *
     * @return mixed
     */
    public function retrieve()
    {
        if (@gettype($this->result->num_rows) == null) {
            return false;
        }
        return @$this->result;

    }
}