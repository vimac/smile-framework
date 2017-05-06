<?php


namespace Smile\Dispatcher;


use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Smile\Controller\BaseController;
use Smile\Exceptions\DispatcherException;
use Smile\Interfaces\ContainerInterface;
use Smile\Interfaces\DispatcherInterface;
use Smile\Router\Route;

class Dispatcher implements DispatcherInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(Route $route, ServerRequestInterface $request)
    {
        $this->assertHasTarget($route);
        $target = $route->getTarget();

        $callback = null;
        if (is_string($target) and class_exists($target, true)) {
            $pathComponents = explode('/', $request->getUri()->getPath());
            $lastComponent = array_pop($pathComponents);
            //尝试从容器中获取对象
            $obj = $this->container->getByType($target);
            $callback = [$obj, $lastComponent];
        } elseif (is_callable($target) and is_array($target) and count($target) == 2) {
            $class = array_shift($target);
            $method = array_shift($target);

            if (is_object($class)) {
                $callback = [$class, $method];
            } else {
                //尝试从容器中获取对象
                $obj = $this->container->getByType($class);
                $callback = [$obj, $method];
            }
        } elseif ($target instanceof Closure) {
            $callback = $target;
        } else {
            throw new DispatcherException(sprintf('匹配的路由规则 %s 的路由目标无效', $route->getUrlRule()));
        }

        return $callback;
    }

    private function assertHasTarget(Route $route)
    {
        if (!$route->hasTarget()) {
            throw new DispatcherException('匹配的路由规则没有路由目标');
        }
    }


}