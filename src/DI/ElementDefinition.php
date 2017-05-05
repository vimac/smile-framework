<?php


namespace Smile\DI;


/**
 * 依赖注入容器中的元素的实现类
 *
 * @package Smile\DI
 */
class ElementDefinition
{

    const SCOPE_PROTOTYPE = 'prototype';
    const SCOPE_SINGLETON = 'singleton';

    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOL = 'bool';
    const TYPE_ARRAY = 'array';
    const TYPE_CALLABLE = 'callable';
    const BASE_TYPES = [self::TYPE_STRING, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_BOOL, self::TYPE_ARRAY, self::TYPE_CALLABLE];

    const NAME_OF_CONSTRUCTOR = '__construct';

    /**
     * @var string
     */
    private $alias;
    /**
     * @var integer
     */
    private $scope = self::SCOPE_PROTOTYPE;
    /**
     * @var boolean
     */
    private $deferred = true;
    /**
     * @var callable
     */
    private $builder;
    /**
     * @var string
     */
    private $type;
    /**
     * @var mixed
     */
    private $instance;
    /**
     * @var bool
     */
    private $baseType = false;

    /**
     * 获取类型
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 获得这个元素定义的别名
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 获得创建实例的回调函数
     * @return callable
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * 获取实例 (仅在作用域 = SCOPE_PROTOTYPE) 时有效
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * 获取是否是单例作用域
     * @return bool
     */
    public function isSingletonScope()
    {
        return $this->scope === self::SCOPE_SINGLETON;
    }

    /**
     * 获取是否是原型作用域
     * @return bool
     */
    public function isPrototypeScope()
    {
        return $this->scope === self::SCOPE_PROTOTYPE;
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
     * 返回这个定义是否立即初始化
     * @return bool
     */
    public function isEager()
    {
        return !$this->deferred;
    }

    /**
     * 判断是否在使用构造方法为构造回调函数
     * @return boolean
     */
    public function isBuilderEqualsConstructor()
    {
        return $this->builder === self::NAME_OF_CONSTRUCTOR;
    }

    /**
     * 返回这个是否是一个基础类型
     * @return bool
     */
    public function isBaseType()
    {
        return $this->baseType;
    }

    /**
     * 返回是否已经存在一个实例
     * @return bool
     */
    public function isInstanceNull()
    {
        return is_null($this->instance);
    }

    /**
     * 设置类型, 如果是基础类型, 请使用 ElementDefinition::TYPE_* 定义的类型
     * @param string $type
     * @return ElementDefinition
     */
    public function setType($type)
    {
        if (in_array($type, self::BASE_TYPES)) {
            $this->baseType = true;
        } else {
            $this->baseType = false;
        }
        $this->type = $type;
        return $this;
    }

    /**
     * 设置这个元素定义的别名
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 设置为单例作用域
     * @return $this
     */
    public function setSingletonScope()
    {
        $this->scope = self::SCOPE_SINGLETON;
        return $this;
    }

    /**
     * 设置为原型作用域
     * @return $this
     */
    public function setPrototypeScope()
    {
        $this->setDeferred();
        $this->scope = self::SCOPE_PROTOTYPE;
        return $this;
    }

    /**
     * 设置这个定义是否延迟初始化
     * @return $this
     */
    public function setDeferred()
    {
        $this->deferred = true;
        return $this;
    }

    /**
     * 设置这个定义立即初始化
     * @return $this
     */
    public function setEager()
    {
        $this->deferred = false;
        return $this;
    }
    /**
     * 设置创建实例的回调函数
     * @param callable $builder
     * @return $this
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * 直接设置类的构造方法为构造回调函数
     * @return $this
     */
    public function setBuilderToConstructor()
    {
        if ($this->builder != self::NAME_OF_CONSTRUCTOR) {
            $this->builder = self::NAME_OF_CONSTRUCTOR;
        }
        return $this;
    }

    /**
     * 设置实例 (会隐式的将 作用域 设置为单例)
     * @param mixed $instance
     * @return $this
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        $this->setSingletonScope();
        return $this;
    }

}