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
     * 给容器设置一个元素
     *
     * @param ElementDefinition $definition 元素定义
     * @return void
     * @throws ContainerException
     */
    public function set(ElementDefinition $definition);

    /**
     * 根据类型从容器获得一个元素产生的实例
     *
     * @param string $type 类型完整名称
     * @return mixed
     * @throws ContainerException
     */
    public function getByType($type);

    /**
     * 根据名称从容器获得一个元素产生的实例
     *
     * @param string $alias 元素名称
     * @return mixed
     * @throws ContainerException
     */
    public function getByAlias($alias);

    /**
     * 给某个命名空间开启自动组装
     * 从使用效果来讲, 期望等价于依次给某个命名空间下的类调用:
     * $container->set(
     *      (new ElementDefinition())
     *          ->setType(MyClass::class)
     *          ->setBuilderToConstructor()
     *          ->setDeferred()
     *          ->setPrototypeScope()
     * );
     * 不过一直到类名被getByType访问之前, 都不会被调用
     *
     * @fixme 严格的讲, 这个不应该属于容器的职责, 大家可以考虑一下如何把这部分逻辑剥离出容器的接口
     *
     * @param string $namespace 待注册的命名空间
     * @return void
     * @throws ContainerException
     */
    public function enableAutowiredForNamespace($namespace);

}