<?php


namespace Smile\DI;


/**
 * 依赖注入容器中的元素的实现类
 *
 * @package Smile\DI
 */
class ElementDefinition
{

    const SCOPE_PROTOTYPE = 1;
    const SCOPE_SINGLETON = 2;

    /**
     * @var string
     */
    private $name;
    /**
     * @var integer
     */
    private $scope;
    /**
     * @var boolean
     */
    private $deferred;
    /**
     * @var boolean
     */
    private $simple;
    /**
     * @var callable
     */
    private $creator;
    /**
     * @var object
     */
    private $instance;
    /**
     * @var mixed
     */
    private $data;

    /**
     * 获取这个定义上绑定的数据 (仅在为简单定义时生效)
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置这个定义上绑定的数据 (仅在简单定义时生效)
     * @param mixed
     * @return ElementDefinition
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获得这个元素定义的名称
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置这个元素定义的名称
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 获得元素定义的范围
     * @return int
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * 设置元素定义的范围
     * @param int $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * 返回这个定义是否延迟初始化
     * @return bool
     */
    public function isDeferred()
    {
        return $this->deferred;
    }

    /**
     *
     * @param bool $deferred
     * @return $this
     */
    public function setDeferred($deferred)
    {
        $this->deferred = $deferred;
        return $this;
    }

    /**
     * 获取这是否是一个简单的定义
     * @return bool
     */
    public function isSimple()
    {
        return $this->simple;
    }

    /**
     * @param bool $simple
     * @return $this
     */
    public function setSimple($simple)
    {
        $this->simple = $simple;
        return $this;
    }

    /**
     * @return callable
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param callable $creator
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return object
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param object $instance
     * @return $this
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

}