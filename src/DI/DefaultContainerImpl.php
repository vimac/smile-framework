<?php


namespace Smile\DI;


/**
 * 依赖注入容器接口的默认实现, 可以运用在大部分的场景下
 *
 * @package Smile\DI
 */
class DefaultContainerImpl implements Container
{
    /**
     * 用来保存元素的Map
     * 是一个关联数组, 其中 key 为元素定义的名称
     * 值为元素定义的对象 @see ElementDefinition
     *
     * @var ElementDefinition[]
     */
    private $definitionNameMap;

    /**
     * 另一个维度的元素的Map
     * 是一个关联数组, 其中 key 为元素定义的实例类型
     * 值为元素定义的对象 @see ElementDefinition
     *
     * @var ElementDefinition[]
     */
    private $definitionTypeMap;

    /**
     * 自动组装的命名空间, 在这个数组里面的命名空间会被激活自动组装机制
     *
     * @var string[]
     */
    private $autowiredNamespaces;

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
     * @return mixed
     */
    public function set($name, callable $creator = null, $options = self::OPTION_EAGERLY | self::OPTION_SCOPE_PROTOTYPE)
    {
        //断言名称合法
        $this->assertNameAvailable($name);

        $definition = new ElementDefinition();
        $definition->setName($name);

        if (empty($creator)) {
            //如果没有配置构造函数, 则尝试寻找$name名称的类的构造函数

            //断言类名合法
            $this->assertClassAvailable($name);

            //使用类的构造方法作为元素的创建方法
            $definition->setCreator([$name, '__constructor']);
        } else {
            $definition->setCreator($creator);
        }

        $this->parseOptions($definition, $options);

        $definition->setSimple(false);

        //保存定义
        $this->definitionNameMap[$name] = $definition;
    }

    /**
     * 给容器设定一个元素, 并直接指定一个实例
     * (由于缺失构造方法, 只适用于单例 SCOPE)
     *
     * @param string $name 名称
     * @param object $instance 实例
     * @return mixed
     * @throws ContainerException
     */
    public function setInstance($name, $instance)
    {
        $this->assertNameAvailable($name);

        if (is_object($instance) and !is_callable($instance)) {
            $definition = new ElementDefinition();
            $definition->setName($name)
                ->setScope(ElementDefinition::SCOPE_SINGLETON)
                ->setDeferred(false)
                ->setInstance($instance)
                ->setSimple(false);

            $this->definitionNameMap[$name] = $definition;
        } else {
            throw new ContainerException(sprintf('%s 只接受对象类型', __METHOD__));
        }
    }

    /**
     * 给容器设定一个元素, 并直接绑定一个简单值
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws ContainerException
     */
    public function setSimpleValue($name, $value)
    {
        $this->assertNameAvailable($name);

        $definition = new ElementDefinition();
        $definition->setName($name)
            ->setSimple(true)
            ->setScope(ElementDefinition::SCOPE_SINGLETON);
        if (is_callable($value)) {
            //如果 $value 是一个 callable 对象, 则使用延迟加载
            $definition->setDeferred(true)
                ->setCreator($value);
        } elseif (is_object($value)) {
            //不接受对象值
            throw new ContainerException(sprintf('%s 只接受基本数据类型', __METHOD__));
        } else {
            //如果 $value 是基本数据类型, 则直接赋值
            $definition->setDeferred(false)
                ->setData($value);
        }

        $this->definitionNameMap[$name] = $definition;
    }

    /**
     * 给某个命名空间开启自动组装
     * 从使用效果来讲, 期望等价于依次给某个命名空间下的类调用:
     * $container->set(MyClass::class, null, Container::OPTION_DEFERRED | Container::OPTION_SCOPE_PROTOTYPE);
     *
     * @fixme 严格的讲, 这个不应该属于容器的职责, 大家可以考虑一下如何把这部分逻辑剥离出容器的接口
     *
     * @param string $namespace
     * @return mixed
     * @throws ContainerException
     */
    public function enableAutowiredForNamespace($namespace)
    {
        $this->assertNamespaceAvailable($namespace);
        $this->autowiredNamespaces[] = $namespace;
    }

    /**
     * 从容器获得一个元素产生的实例
     *
     * @param string $name 元素名称
     * @return mixed
     * @throws ContainerException
     */
    public function get($name)
    {
        $definition = null;
        if (isset($this->definitionNameMap[$name])) {
            $definition = $this->definitionNameMap[$name];
        } elseif ($this->searchAutowiredNamespace($name)) {
            $this->set($name, null, self::OPTION_DEFERRED | self::OPTION_SCOPE_PROTOTYPE);
            $definition = $this->definitionNameMap[$name];
        }
        $this->assertDefinitionAvailable($definition);

        return $this->getInstanceFromDefinition($definition);
    }

    /**
     * 根据类型从容器获得一个元素产生的实例
     *
     * @param $typeName
     * @return mixed
     */
    public function getByType($typeName)
    {
        $definition = null;
        if (isset($this->definitionTypeMap[$typeName])) {
            $definition = $this->definitionTypeMap[$typeName];
        }
    }

    /**
     * 解析选项
     *
     * @param ElementDefinition $definition
     * @param $options
     */
    private function parseOptions(ElementDefinition $definition, $options)
    {
        if ($options & self::OPTION_DEFERRED) {
            //延迟创建
            $definition->setDeferred(true);
        } else {
            //默认为立即创建
            $definition->setDeferred(false);
        }

        if ($options & self::OPTION_SCOPE_SINGLETON) {
            //单例
            $definition->setScope(ElementDefinition::SCOPE_SINGLETON);
        } else {
            //原型
            $definition->setScope(ElementDefinition::SCOPE_PROTOTYPE);
        }
    }

    /**
     * 搜索是否命中自动组装的命名空间
     *
     * @param $name
     * @return bool
     */
    private function searchAutowiredNamespace($name)
    {
        foreach ($this->autowiredNamespaces as $ns) {
            if (substr_compare($name, $ns, 0, strlen($ns)) === 0) {
                return true;
            }
        }
        return false;
    }


    /**
     * 断言某个名称是一个有效的字符串, 如果无效, 则抛出异常
     *
     * @param string $name 待检查的名称
     * @return bool
     * @throws ContainerException
     */
    private function assertNameAvailable($name)
    {
        if (!is_string($name) or empty($name)) {
            throw new ContainerException('不是一个合法的元素名称');
        }
    }

    /**
     * 断言某个类已经定义
     *
     * @param string $nameOfClass 类名
     * @throws ContainerException
     */
    private function assertClassAvailable($nameOfClass)
    {
        //激活自动加载来判断是否存在这个类
        if (!class_exists($nameOfClass, true)) {
            throw new ContainerException('未定义的类名');
        }
    }

    /**
     * 断言某个元素定义有效
     *
     * @param ElementDefinition|null $definition
     * @throws ContainerException
     */
    private function assertDefinitionAvailable(ElementDefinition $definition = null)
    {
        if (!isset($definition)) {
            throw new ContainerException('元素定义不存在');
        }
    }

    /**
     * 断言某个命名空间有效
     *
     * @param $namespace
     * @throws ContainerException
     */
    private function assertNamespaceAvailable($namespace)
    {
        if (!is_string($namespace) or empty($namespace)) {
            throw new ContainerException('不是一个合法的命名空间');
        }
    }

    /**
     * 从元素定义实例化对象
     *
     * @param ElementDefinition $definition
     */
    private function getInstanceFromDefinition(ElementDefinition $definition)
    {
        if ($definition->isDeferred()) {
            $value = $this->getInstanceFromDefinition($definition);
        } else {
            if ($definition->isSimple()) {
                $value = $definition->getData();
            } else {
                $value = $definition->getInstance();
            }
        }

        $reflectionFunc = new \ReflectionClass($definition->getCreator());

    }
}