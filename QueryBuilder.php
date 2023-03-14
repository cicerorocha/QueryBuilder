<?php

use PDO;
use PDOException;

/**
 * Summary of QueryBuilder
 */
class QueryBuilder
{

    private $pdo;

    private $table;

    private $select = '*';

    private $join = '';

    private $leftJoin = '';

    private $rightJoin = '';

    private $limit = '';

    private $where = '';

    private $groupBy = '';

    private $orderBy = '';

    private $params = [];

    public function __construct()
    {
        $host = "";
        $port = "5432";
        $dbname = "";
        $user = "";
        $password = "";

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->pdo = new PDO($dsn, $user, $password, $options);


    }
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($select)
    {
        $this->select = $select;
        return $this;
    }

    public function join($table, $column1, $operator, $column2)
    {
        $this->join .= " JOIN $table ON $column1 $operator $column2";
        return $this;
    }

    public function leftJoin($table, $column1, $operator, $column2)
    {
        $this->join .= " LEFT JOIN $table ON $column1 $operator $column2";
        return $this;
    }

    public function rightJoin($table, $column1, $operator, $column2)
    {
        $this->join .= " RIGHT JOIN $table ON $column1 $operator $column2";
        return $this;
    }
    public function limit($limit)
    {
        $this->limit = " LIMIT $limit";
        return $this;
    }
    public function orderBy($column, $order)
    {
        $this->orderBy = " ORDER BY $column $order";
        return $this;
    }
    public function groupBy($column)
    {
        $this->groupBy = " GROUP BY $column";
        return $this;
    }
    public function where($column, $operator, $value)
    {
        if (!$this->where) {
            $this->where = "WHERE $column $operator ?";
        } else {
            $this->where .= " AND $column $operator ?";
        }
        $this->params[] = $value;
        return $this;
    }
    public function orWhere($column, $operator, $value)
    {
        if (!$this->where) {
            $this->where = "WHERE $column $operator ?";
        } else {
            $this->where .= " OR $column $operator ?";
        }
        $this->params[] = $value;
        return $this;
    }
    public function insert($values)
    {
        try {
            $this->pdo->beginTransaction();
            $columns = implode(',', array_keys($values));
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $sql = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($values));
            $lastInsertId = $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $lastInsertId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }
    public function update($values)
    {
        try {
            $this->pdo->beginTransaction();
            $set = implode(',', array_map(function ($key) {
                return $key;
            }, array_keys($values)));
            $sql = "UPDATE $this->table SET $set $this->where";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->params);
            $rowCount = $stmt->rowCount();
            $this->pdo->commit();
            return $rowCount;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }
    public function delete()
    {
        try {
            $this->pdo->beginTransaction();
            $sql = "DELETE FROM $this->table $this->where";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->params);
            $rowCount = $stmt->rowCount();
            $this->pdo->commit();
            return $rowCount;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }
    public function get()
    {
        try {
            $sql = "SELECT $this->select FROM $this->table $this->join $this->where";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            throw $e;
        }
    }
    private function logError($message)
    {
        $file = fopen('errors.log', 'a');
        fwrite($file, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
        fclose($file);
    }
}