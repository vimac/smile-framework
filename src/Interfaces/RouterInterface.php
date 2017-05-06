<?php
/**
 * Created by IntelliJ IDEA.
 * User: mac
 * Date: 06/05/2017
 * Time: 10:21
 */

namespace Smile\Interfaces;

use Smile\Exceptions\RouterException;
use Smile\Router\Route;

interface RouterInterface
{
    /**
     * 添加一个路由规则
     * @param Route $route
     * @throws RouterException
     */
    public function addRoute(Route $route);

    /**
     * 解析一个 URL, 返回路由规则
     *
     * @param string $method 请求方法
     * @param string $url URL
     * @return Route
     * @throws RouterException
     */
    public function resolve($method, $url);

    /**
     * 根据路由规则名称和参数返回路径 (可用于 URL 生成)
     *
     * @param string $name
     * @param array|null $parts
     * @return string
     */
    public function generatePath($name, array $parts = []);
}