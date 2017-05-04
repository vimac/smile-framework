<?php

namespace Smile\DI;


/**
 * 依赖注入容器的接口
 *
 * @package Smile\DI
 */
interface Container
{
    /**
     * 立即初始化
     */
    const OPTION_EAGERLY = 0b0001;

    /**
     * 延迟初始化
     */
    const OPTION_DEFERRED = 0b0010;

    /**
     * 原型
     */
    const OPTION_SCOPE_PROTOTYPE = 0b0100;

    /**
     * 单例
     */
    const OPTION_SCOPE_SINGLETON = 0b1000;

    /**
     * 给容器设置一个元素
     *
     * 如果 $creator 参数非空, 则将 $creator 视为这个元素的创建方法
     *
     * 如果 $creator 参数为空, 则尝试把 $name 当做类名, 并且把这个类的构造方法当成元素的创建方法,
     * 也就是说等效于 $container->set(MyClass::class, [MyClass::class, '__constructor']);
     *
     * @param string $name 名称
     * @param callable $creator 构造方法或者初始化回调函数
     * @param int $options 选项, 默认为(立即初始化, 原型构建)
     * @return void
     */
    public function set($name, callable $creator = null, $options = self::OPTION_EAGERLY | self::OPTION_SCOPE_PROTOTYPE);

    /**
     * 给容器设定一个元素, 并直接指定一个实例
     * (由于缺失构造方法, 只适用于单例 SCOPE)
     *
     * @param string $name 名称
     * @param object $instance 实例
     * @return void
     * @throws ContainerException
     */
    public function setInstance($name, $instance);

    /**
     * 给容器设定一个元素, 并直接绑定一个简单值
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws ContainerException
     */
    public function setSimpleValue($name, $value);

    /**
     * 给某个命名空间开启自动组装
     * 从使用效果来讲, 期望等价于依次给某个命名空间下的类调用:
     * $container->set(MyClass::class, null, Container::OPTION_DEFERRED | Container::OPTION_SCOPE_PROTOTYPE);
     *
     * @fixme 严格的讲, 这个不应该属于容器的职责, 大家可以考虑一下如何把这部分逻辑剥离出容器的接口
     *
     * @param string $namespace 待注册的命名空间
     * @return void
     * @throws ContainerException
     */
    public function enableAutowiredForNamespace($namespace);

    /**
     * 根据名称从容器获得一个元素产生的实例
     *
     * @param string $name 元素名称
     * @return mixed
     * @throws ContainerException
     */
    public function get($name);

    /**
     * 根据类型从容器获得一个元素产生的实例
     *
     * @param $typeName
     * @return mixed
     */
    public function getByType($typeName);

}