<?php
/**
 * Created by PhpStorm.
 * User: HePing
 * Date: 2019-03-02
 * Time: 22:28
 */
namespace INocturneSwoole\Db;

use INocturneSwoole\Connection\MySQLPool;
use Swoole\Coroutine\MySQL;
use Swoole\Coroutine\Mysql\Statement;

class Query implements \IteratorAggregate
{
    private $select;

    private $insert;

    private $update;

    private $delete;

    private $values;

    private $set;

    private $from;

    private $where = [];

    private $joins;

    private $entity;

    private $group;

    private $order;

    private $limit;

    private $params = [];

    private $conName;
    /**
     * @var MySQL
     */
    private $db;

    public function __construct(MySQL $db, $conName = null)
    {
        $this->db      = $db;
        $this->conName = $conName;
    }

    /**
     * @return QueryResult|\Traversable
     * @throws \Exception
     */
    public function getIterator()
    {
        return $this->fetchAll();
    }

    /**
     * @return QueryResult|array
     * @throws \Exception
     * Author: HePing
     * Email:  847050412@qq.com
     * Date:  2019-03-05
     * Time: 10:39
     */
    public function fetchAll() : QueryResult
    {
        if ($this->execute() instanceof Statement) {
            return new QueryResult($this->execute()->fetchAll(), $this->entity);
        }
        return new QueryResult($this->execute());
    }

    /**
     * @return bool|mixed
     * @throws \Exception
     */
    public function fetch()
    {
        if ($this->execute() instanceof Statement) {
            $record = $this->execute()->fetch();
        } else {
            $record = $this->execute();
        }

        if (false === $record) {
            return false;
        }
        if ($this->entity) {
            return Builder::hydrate($record, $this->entity);
        }
        return $record;
    }

    /**
     * @return bool|mixed
     * @throws \Exception
     */
    public function fetchOrFail()
    {
        $record = $this->fetch();
        if (false === $record) {
            throw new \Exception('No query results for model');
        }
        return $record;
    }

    /**
     * @param string      $table
     * @param string|null $alias
     *
     * @return Query
     */
    public function from(string $table, ?string $alias = null) : self
    {
        if ($alias) {
            $this->from[$table] = $alias;
        } else {
            $this->from[] = $table;
        }
        return $this;
    }

    /**
     * @param string ...$fields
     *
     * @return Query
     */
    public function select(string ...$fields) : self
    {
        $this->select = $fields;
        return $this;
    }

    /**
     * @param string     $table
     * @param array|null $attributes
     *
     * @return Query
     */
    public function insert(string $table, ?array $attributes = null) : self
    {
        $this->insert = $table;
        if ($attributes) {
            $this->values = $attributes;
        }
        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return Query
     */
    public function value(array $attributes) : self
    {
        $this->values = $attributes;
        return $this;
    }

    public function update(string $table, ?array $attributes = null, ? int $id = null) : self
    {
        $this->update = $table;
        if ($id) {
            $this->where('id = ' . intval($id));
        }
        if ($attributes) {
            $this->set = $attributes;
        }
        return $this;
    }

    public function set(array $attributes) : self
    {
        $this->set = $attributes;
        return $this;
    }

    public function delete(string $table, ?int $id = null) : self
    {
        $this->delete = $table;
        if ($id) {
            $this->where('id = ' . intval($id));
        }
        return $this;
    }

    /**
     * @param string ...$conditions
     *
     * @return Query
     */
    public function where(string ...$conditions) : self
    {
        $this->where = array_merge($this->where, $conditions);
        return $this;
    }

    /**
     * @param string $table
     * @param string $condition
     * @param string $type
     *
     * @return Query
     */
    public function join(string $table, string $condition, string $type = 'left') : self
    {
        $this->joins[$type][] = [$table, $condition];
        return $this;
    }

    public function params(array $params, bool $merge = true) : self
    {
        if ($merge) {
            $this->params = array_merge($this->params, $params);
        } else {
            $this->params = $params;
        }
        return $this;
    }

    public function orderBy(string $column, ?string $direction = 'ASC') : self
    {
        $this->order[$column] = $direction;
        return $this;
    }

    public function groupBy(string $column) : self
    {
        $this->group = $column;
        return $this;
    }

    public function into(string $entity) : self
    {
        $this->entity = $entity;
        return $this;
    }

    public function limit(int $limit, int $offset = 0) : self
    {
        $this->limit = "$offset, $limit";
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $parts = ['SELECT'];
        if ($this->select) {
            $parts[] = implode(', ', $this->select);
        } else {
            $parts[] = '*';
        }
        if ($this->insert) {
            $parts = ['INSERT INTO ' . $this->insert];
        }
        if ($this->values) {
            $parts[]      = '(' . implode(', ', array_values($this->values)) . ')';
            $parts[]      = 'VALUES';
            $appendValStr = '(';
            $appendValStr .= implode('', array_map(function ($value)
            {
                return '?,';
            }, $this->values));
            $appendValStr = rtrim($appendValStr, ',');
            $appendValStr .= ')';
            $parts[]      = $appendValStr;
        }
        if ($this->update) {
            $parts = ['UPDATE ' . $this->update . ' SET'];
        }
        if ($this->set) {
            $sets = [];
            foreach ($this->set as $key => $value) {
                $sets[] = "$value = ?";
            }
            $parts[] = implode(', ', $sets);
        }
        if ($this->delete) {
            $parts = ['DELETE FROM ' . $this->delete];
        }
        if ($this->from) {
            $parts[] = 'FROM';
            $parts[] = $this->buildFrom();
        }
        if (!empty($this->where)) {
            $parts[] = 'WHERE';
            $parts[] = '(' . implode(') AND (', $this->where) . ')';
        }
        if (!empty($this->joins)) {
            foreach ($this->joins as $type => $joins) {
                foreach ($joins as [$table, $condition]) {
                    $parts[] = mb_strtoupper($type) . " JOIN $table ON $condition";
                }
            }
        }
        if ($this->order) {
            foreach ($this->order as $key => $value) {
                $parts[] = "ORDER BY $key $value";
            }
        }
        if ($this->group) {
            $parts[] = 'GROUP BY ' . $this->group;
        }
        if ($this->limit) {
            $parts[] = 'LIMIT ' . $this->limit;
        }
        return implode(' ', $parts);
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function count() : ?int
    {
        $query  = clone $this;
        $table  = current($this->from);
        $result = $query->select("COUNT({$table}.id) as `cnt`")->execute();
        if (is_array($result) && $result !== false && count($result) > 0) {
            return $result[0]['cnt'];
        }
        return null;
    }

    private function buildFrom() : string
    {
        $from = [];
        foreach ($this->from as $key => $value) {
            if (\is_string($key)) {
                $from[] = "$key as $value";
            } else {
                $from[] = $value;
            }
        }
        return implode(', ', $from);
    }

    /**
     * @return array|bool|\Swoole\Coroutine\Mysql\Statement
     * @throws \Exception
     */
    public function execute()
    {
        if (!empty($this->params)) {
            $statement = $this->_getStatement($this->db, $this->__toString());
            if (!$result = $statement->execute($this->params)) {
                throw new \Exception("Sql Error by execute query: {$this->__toString()}");
            }
            return $result;
        }
        return $this->db->query($this->__toString());
    }

    /**
     * @param MySQL $connection
     * @param       $sql
     *
     * @return mixed
     */
    protected function _getStatement(MySQL $connection, $sql)
    {
        $statement = $connection->prepare($sql);

        if ($statement == false) {
            //断线重连
            $connection = MySQLPool::reconnect($connection, $this->conName);
            $statement  = $connection->prepare($sql);
            defer(function () use ($connection)
            {
                MySQLPool::recycle($connection);
            });
        }
        return $statement;
    }

}