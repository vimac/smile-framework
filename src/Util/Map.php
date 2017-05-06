<?php


namespace Smile\Util;


use Smile\Interfaces\MapInterface;

class Map implements MapInterface
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get($key, $defaultValue = null)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return $defaultValue;
        }
    }

    public function getAll()
    {
        return $this->data;
    }

    public function keys()
    {
        return array_keys($this->data);
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->data[$key]);
    }

    public function clear()
    {
        $this->data = [];
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}