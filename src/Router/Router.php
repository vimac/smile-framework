<?php


namespace Smile\Router;


use Smile\Exceptions\RouterException;
use Smile\Interfaces\RouterInterface;

class Router implements RouterInterface
{
    /**
     * 这是一个关联数组, key 为 Route 的名称, value 为 Route
     * @var array
     */
    private $nameMap;

    /**
     * 这是一个二级关联数组, 结构:
     * [
     *     'get' => [
     *         Route, Route, Route
     *     ],
     *     'post' => [
     *         Route, Route
     *     ],
     * ]
     * @var array
     */
    private $methodMap = [];

    /**
     * 添加一个路由规则
     * @param Route $route
     * @throws RouterException
     */
    public function addRoute(Route $route)
    {
        if (!$route->hasMethods()) {
            throw new RouterException('路由方法未定义');
        }
        if (!$route->hasTarget()) {
            throw new RouterException('路由目标未定义');
        }

        $methods = $route->getMethods();
        foreach ($methods as $method) {
            $this->methodMap[$method][] = $route;
        }

        if ($route->hasName()) {
            $name = $route->getName();
            if (!empty($this->nameMap[$name])) {
                throw new RouterException(sprintf('这个名称的路由规则已经存在: %s', $name));
            }
            $this->nameMap[$name] = $route;
        }
    }

    /**
     * 解析一个 URL, 返回路由规则
     *
     * @param string $method 请求方法
     * @param string $url URL
     * @return Route
     * @throws RouterException
     */
    public function resolve($method, $url)
    {
        $method = strtolower($method);
        if (!in_array($method, Route::METHODS)) {
            throw new RouterException(sprintf('不支持的请求方法: %s $url', $method, $url));
        }

        /** @var Route[] $routes */
        $routes = $this->methodMap[$method];

        foreach ($routes as $route) {
            if ($route->matchUrl($url)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * 根据路由规则名称和参数返回路径 (可用于 URL 生成)
     *
     * @param string $name
     * @param array|null $parts
     * @return string
     */
    public function generatePath($name, array $parts = [])
    {
        $route = $this->getRouteByName($name);
        return $route->generatePath($parts);
    }

    /**
     * 根据名称获得路由规则
     *
     * @param $name
     * @return null|Route
     * @throws RouterException
     */
    private function getRouteByName($name)
    {
        if (!empty($this->nameMap[$name])) {
            return $this->nameMap[$name];
        } else {
            throw new RouterException(sprintf('不存在的路由规则名称: %s', $name));
        }
    }

}