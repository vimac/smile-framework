<?php


namespace Smile\Di;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Smile\Exceptions\ContainerException;
use Smile\Interfaces\ContainerInterface;


/**
 * 依赖注入容器接口的默认实现, 可以运用在大部分的场景下
 *
 * @package Smile\Di
 */
class Container implements ContainerInterface
{
    /**
     * 元素的Map
     * 是一个关联数组, 其中 key 为元素定义的实例类型
     * 值为元素定义的对象 @see ElementDefinition
     *
     * @var ElementDefinition[]
     */
    private $definitionTypeMap = [];

    /**
     * 另一个维度的元素的Map
     * 是一个关联数组, 其中 key 为元素定义的别名
     * 值为元素定义的对象 @see ElementDefinition
     *
     * @var ElementDefinition[]
     */
    private $definitionAliasMap = [];

    /**
     * 自动组装的命名空间, 在这个数组里面的命名空间会被激活自动组装机制
     *
     * @var string[]
     */
    private $autowiredNamespaces = [];

    /**
     * 给容器设置一个元素
     *
     * @param ElementDefinition $definition 元素定义
     * @return void
     * @throws ContainerException
     */
    public function set(ElementDefinition $definition)
    {
        //断言类型合法
        $this->assertTypeNameAvailable($definition->getType());

        //断言作用域
        $this->assertScope($definition);

        //如果没有设置实例则尝试初始化builder
        if ($definition->isInstanceNull()) {
            $this->initializeBuilder($definition);
        }

        if (!$definition->isBaseType()) {
            //保存到map中
            $this->definitionTypeMap[$definition->getType()] = $definition;
        } else {
            //如果是基本类型, 校验基本类型的合法性
            $this->assertBaseType($definition);
        }

        $alias = $definition->getAlias();
        if (!empty($alias)) {
            $this->assertAliasAvailable($alias);
            $this->definitionAliasMap[$definition->getAlias()] = $definition;
        }

        $this->initializeEagerDefinition($definition);
    }

    /**
     * 根据类型从容器获得一个元素产生的实例
     *
     * @param $type
     * @return mixed
     */
    public function getByType($type)
    {
        return $this->buildByTypeRecursive($type);
    }

    /**
     * 从容器获得一个元素产生的实例
     *
     * @param string $alias 元素别名
     * @return mixed
     * @throws ContainerException
     */
    public function getByAlias($alias)
    {
        return $this->buildByAliasRecursive($alias);
    }

    /**
     * 给某个命名空间开启自动组装
     * 从使用效果来讲, 期望等价于依次给某个命名空间下的类调用:
     * $container->set(
     *      (new ElementDefinition())
     *          ->setType(MyClass::class)
     *          ->setDeferred()
     *          ->setPrototypeScope()
     * );
     * 不过一直到类名被getByType访问之前, 都不会被调用
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
     * 递归的创建实例
     * 第二个参数主要用于检查循环依赖
     *
     * @param string $type 类型名
     * @param array $stack 依赖栈
     * @return mixed
     * @throws ContainerException
     */
    private function buildByTypeRecursive($type, array $stack = [])
    {
        $definition = null;
        $this->assertNoCircleDependency($type, $stack);
        $stack[] = $type;

        if (isset($this->definitionTypeMap[$type])) {
            $definition = $this->definitionTypeMap[$type];
        } elseif ($this->searchAutowiredNamespace($type)) {
            $autoDefinition = (new ElementDefinition())
                ->setType($type)
                ->setBuilderToConstructor()
                ->setPrototypeScope()
                ->setDeferred();
            $this->set($autoDefinition);
            $definition = $autoDefinition;
        } else {
            throw new ContainerException(sprintf('找不到定义: %s, 依赖栈: %s', $type, json_encode($stack)));
        }

        if ($definition->isSingletonScope() and !$definition->isInstanceNull()) {
            // 单例并且已经初始化的实例直接返回
            $result = $definition->getInstance();
            $this->assertResultType($definition, $result);
            return $result;
        }
        $result = $this->callBuilder($definition, $stack);
        $this->assertResultType($definition, $result);
        if ($definition->isSingletonScope()) {
            // 如果是单例, 保存这个实例
            $definition->setInstance($result);
        }
        return $result;
    }

    /**
     * 递归的创建实例
     * 第二个参数主要用于检查循环依赖
     *
     * @param string $alias 别名
     * @param array $stack 依赖栈
     * @return mixed
     * @throws ContainerException
     */
    private function buildByAliasRecursive($alias, array $stack = [])
    {
        $definition = null;
        $this->assertNoCircleDependency('$' . $alias, $stack);
        $stack[] = '$' . $alias;

        if (isset($this->definitionAliasMap[$alias])) {
            $definition = $this->definitionAliasMap[$alias];
        } else {
            throw new ContainerException(sprintf('找不到别名: %s, 依赖栈: %s', $alias, json_encode($stack)));
        }

        if ($definition->isSingletonScope() and !$definition->isInstanceNull()) {
            // 单例并且已经初始化的实例直接返回
            $result = $definition->getInstance();
            $this->assertResultType($definition, $result);
            return $result;
        }
        $result = $this->callBuilder($definition, $stack);
        $this->assertResultType($definition, $result);
        if ($definition->isSingletonScope()) {
            // 如果是单例, 保存这个实例
            $definition->setInstance($result);
        }
        return $result;
    }

    /**
     * 搜索是否命中自动组装的命名空间
     *
     * @param string $name 类名
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
     * 断言某个类型是否是有效的类型, 如果无效, 则抛出异常
     * (但不发起类是否存在的验证)
     *
     * @param string $type 待检查的类型
     * @throws ContainerException
     */
    private function assertTypeNameAvailable($type)
    {
        if (!is_string($type) or empty($type)) {
            throw new ContainerException('不是一个合法的类型');
        }
        if (array_key_exists($type, $this->definitionTypeMap)) {
            throw new ContainerException('类型已经存在定义');
        }
    }


    /**
     * 断言某个别名是一个有效的字符串, 如果无效, 则抛出异常
     * 一个别名只允许被设置一次
     *
     * @param string $name 待检查的别名
     * @throws ContainerException
     */
    private function assertAliasAvailable($name)
    {
        if (!is_string($name)) {
            throw new ContainerException('不是一个合法的元素别名');
        }
        if (array_key_exists($name, $this->definitionAliasMap)) {
            throw new ContainerException('别名已经存在');
        }
    }

    /**
     * 校验基本类型是否合法
     *
     * @param ElementDefinition $definition
     * @throws ContainerException
     */
    private function assertBaseType(ElementDefinition $definition)
    {
        if (empty($definition->getAlias())) {
            throw new ContainerException('基本类型的元素定义必须设置别名');
        }
    }


    /**
     * 断言某个命名空间有效
     *
     * @param string $namespace 命名空间
     * @throws ContainerException
     */
    private function assertNamespaceAvailable($namespace)
    {
        if (!is_string($namespace) or empty($namespace)) {
            throw new ContainerException('不是一个合法的命名空间');
        }
    }

    /**
     * 断言元素定义下面的作用域设置
     *
     * @param ElementDefinition $definition 待检查的元素定义
     * @throws ContainerException
     */
    private function assertScope(ElementDefinition $definition)
    {
        if ($definition->isSingletonScope()) {
            return;
        } elseif ($definition->isPrototypeScope()) {
            if ($definition->isEager()) {
                //不支持原型作用域的立即实例化
                throw new ContainerException('原型作用域不支持立即实例化');
            }
            if (!$definition->isInstanceNull()) {
                throw new ContainerException('原型作用域不支持直接设置实例(必须提供builder创建)');
            }
        } else {
            throw new ContainerException('不明作用域');
        }
    }

    private function assertBuilderAvailable(ElementDefinition $definition)
    {
        if (!$definition->isBuilderEqualsConstructor() and !is_callable($definition->getBuilder())) {
            throw new ContainerException('builder不是一个合法的回调方法');
        }
    }

    /**
     * 校验没有循环依赖
     *
     * @param $key
     * @param $stack
     * @throws ContainerException
     */
    private function assertNoCircleDependency($key, $stack)
    {
        if (in_array($key, $stack)) {
            throw new ContainerException(sprintf('存在循环依赖, 依赖栈: %s', json_encode($stack)));
        }
    }

    /**
     * 校验返回值是否和定义的一致
     *
     * @param ElementDefinition $definition
     * @param mixed $buildResult
     * @throws ContainerException
     * @internal param $result
     */
    private function assertResultType(ElementDefinition $definition, $buildResult)
    {
        if ($definition->isBaseType()) {
            if (!call_user_func('is_' . $definition->getType(), $buildResult)) {
                foreach (ElementDefinition::BASE_TYPES as $baseType) {
                    if (call_user_func('is_' . $baseType, $buildResult)) {
                        throw new ContainerException(sprintf('期望返回值类型: %s, 实际返回值类型: %s', $definition->getType(), $baseType));
                    }
                }
            }
        } else {
            if (!is_a($buildResult, $definition->getType())) {
                throw new ContainerException(sprintf('期望返回值类型: %s, 实际返回值类型: %s', $definition->getType(), get_class($buildResult)));
            }
        }

    }

    /**
     * 实例化立即初始化的元素定义
     *
     * @param ElementDefinition $definition
     * @return mixed
     */
    private function initializeEagerDefinition(ElementDefinition $definition)
    {
        // 只初始化需要立即初始化的元素定义
        if ($definition->isEager()) {

            if (!$definition->isInstanceNull()) {
                //如果已经存在instance, 则跳过
                return $definition->getInstance();
            }

            $type = $definition->getType();

            if (class_exists($type, true)) {
                $definition->setInstance(
                    $this->getByType($definition->getType())
                );
            }
        }
    }

    /**
     * 初始化&验证构造回调
     *
     * @param ElementDefinition $definition
     * @throws ContainerException
     */
    private function initializeBuilder(ElementDefinition $definition)
    {
        if (empty($definition->getBuilder())) {
            if ($definition->isBaseType() and $definition->isInstanceNull()) {
                throw new ContainerException('基本类型不支持构造方法');
            }
            //如果builder不存在, 设置类的构造方法为builder
            $definition->setBuilderToConstructor();
        } else {
            //如果设置了builder, 则验证是否是callable的对象
            $this->assertBuilderAvailable($definition);
        }
    }

    /**
     * 调用Builder, 实例化方法
     * @param ElementDefinition $definition
     * @param array $stack
     * @return mixed
     * @throws ContainerException
     */
    private function callBuilder(ElementDefinition $definition, array $stack = [])
    {
        $reflectionClass = null;
        $reflectionFunc = null;
        if ($definition->isBuilderEqualsConstructor()) {
            //判断builder方法定义成了目标类的构造方法
            $reflectionClass = new ReflectionClass($definition->getType());
            $reflectionFunc = $reflectionClass->getConstructor();
            if (empty($reflectionFunc)) {
                //目标对象不存在构造方法, 则直接生成一个对象的实例返回
                return $reflectionClass->newInstance();
            }
            if (!$reflectionFunc->isPublic()) {
                throw new ContainerException(sprintf('构造方法作用域不可见, 依赖栈: %s', json_encode($stack)));
            }
        } else {
            $reflectionFunc = new ReflectionFunction($definition->getBuilder());
        }

        $reflectionParams = $reflectionFunc->getParameters();

        $realParams = [];
        foreach ($reflectionParams as $reflectionParam) {
            $reflectionParamClass = $reflectionParam->getClass();
            $paramClassName = isset($reflectionParamClass) ? $reflectionParamClass->getName() : null;

            if (class_exists($paramClassName, true)) {
                // 找到类型走 typeMap
                $paramInstance = $this->buildByTypeRecursive($paramClassName, $stack);
                $realParams[$reflectionParam->getPosition()] = $paramInstance;
            } else {
                // 找不到类型走 aliasMap
                // FIXME 这个判断方法可以用, 但是不严谨
                $parameterName = $reflectionParam->getName();
                $realParams[$reflectionParam->getPosition()] = $this->getByAlias($parameterName);
            }
        }

        if ($reflectionFunc instanceof ReflectionMethod) {
            $instance = $reflectionClass->newInstanceArgs($realParams);
            return $instance;
        } else {
            $result = $reflectionFunc->invokeArgs($realParams);
        }

        return $result;
    }

}