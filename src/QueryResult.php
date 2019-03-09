<?php
namespace INocturneSwoole\Db;

class QueryResult implements \ArrayAccess, \Iterator, \Countable
{

    private $records;

    private $index = 0;
    private $entity;

    protected static $hydrateCache = [];

    public function __construct(array $records, ?string $entity = null)
    {
        $this->records = $records;
        $this->entity  = $entity;
    }

    public function get(int $index)
    {
        if ($this->entity) {
            if (isset(static::$hydrateCache[$index])) {
                return static::$hydrateCache[$index];
            }
            return static::$hydrateCache[$index] = Builder::hydrate($this->records[$index], $this->entity);
        }
        return $this->entity;
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetExists($offset)
    {
        return isset($this->records[$offset]);
    }

    public function current()
    {
        return $this->get($this->entity);
    }

    public function count()
    {
        return count($this->records);
    }

    public function key()
    {
        return $this->index;
    }

    public function next() : void
    {
        ++$this->index;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @throws \Exception
     * Author: HePing
     * Email:  847050412@qq.com
     * Date:  2019-03-05
     * Time: 10:01
     */
    public function offsetSet($offset, $value)
    {
        throw  new \Exception("Can't alter records");
    }

    /**
     * @param mixed $offset
     *
     * @throws \Exception
     * Author: HePing
     * Email:  847050412@qq.com
     * Date:  2019-03-05
     * Time: 10:01
     */
    public function offsetUnset($offset)
    {
        throw  new \Exception("Can't alter records");
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return isset($this->records[$this->index]);
    }
}