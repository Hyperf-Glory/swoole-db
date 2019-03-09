<?php
namespace INocturneSwoole\Db;

class Builder
{
    protected static $studlyCache = [];
    protected static $camelCache = [];

    public static function hydrate(array $item, $object)
    {
        if (is_string($object)) {
            $instance = new $object();
        } else {
            $instance = $object;
        }
        foreach ($item as $key => $value) {
            $method = 'set' . self::studly($key);
            if (method_exists($instance, $method)) {
                $instance->{$method}($value);
            } else {
                $property              = self::camel($key);
                $instance->{$property} = $value;
            }
        }
        return $instance;
    }

    /**
     * @param $value
     *
     * @return mixed
     * Author: HePing
     * Email:  847050412@qq.com
     * Date:  2019-03-03
     * Time: 14:58
     */
    public static function studly($value)
    {
        $key = $value;
        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    /**
     * @param $value
     *
     * @return mixed|string
     * Author: HePing
     * Email:  847050412@qq.com
     * Date:  2019-03-03
     * Time: 14:58
     */
    public static function camel($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }
        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

}