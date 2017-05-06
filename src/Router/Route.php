<?php


namespace Smile\Router;


use Smile\Util\Map;

class Route
{
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';

    const METHODS = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var string
     */
    private $urlRule;

    /**
     * @var string
     */
    private $regexUrlPattern;

    /**
     * @var array
     */
    private $parts;

    /**
     * @var string
     */
    private $url;

    /**
     * @var mixed
     */
    private $target;

    /**
     * 获取是否设置了名称
     *
     * @return bool
     */
    public function hasName()
    {
        return !empty($this->name);
    }

    /**
     * 获取是否设置了方法
     *
     * @return bool
     */
    public function hasMethods()
    {
        return count($this->methods) > 0;
    }

    /**
     * 获取是否设置了解析目标
     *
     * @return bool
     */
    public function hasTarget()
    {
        return isset($this->target);
    }

    /**
     * 获取名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取所有方法
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * 返回 Map 封装后的 URL 部件
     *
     * @return Map
     */
    public function getParts()
    {
        return new Map($this->parts);
    }

    /**
     * 设置名称
     *
     * @param string $name
     * @return Route
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 设置解析目标
     *
     * @param mixed $target
     * @return Route
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * 映射方法到 URL 规则
     * @param $methods
     * @param $urlRule
     */
    public function map($methods, $urlRule)
    {
        $this->assertUrlRule($urlRule);
        $this->urlRule = $urlRule;
        $this->setupRegexRule();

        if (is_string($methods)) {
            $this->methods = explode('|', $methods);
        } elseif (is_array($methods)) {
            $this->methods = $methods;
        }

        array_walk($this->methods, function (&$method) {
            $method = strtolower($method);
            if (!in_array($method, self::METHODS)) {
                throw new RouteException('不合法的方法');
            }
        });
    }

    /**
     * 为 URL 规则绑定 Restful 协议的几种方法
     * @param $urlRule
     * @return $this
     */
    public function restful($urlRule)
    {
        $this->map(self::METHODS, $urlRule);
        return $this;
    }

    /**
     * 为 URL 规则绑定 GET 方法
     * @param $urlRule
     * @return $this
     */
    public function get($urlRule)
    {
        $this->map([self::METHOD_GET], $urlRule);
        return $this;
    }

    /**
     * 为 URL 规则绑定 POST 方法
     * @param $urlRule
     * @return $this
     */
    public function post($urlRule)
    {
        $this->map([self::METHOD_POST], $urlRule);
        return $this;
    }

    /**
     * 为 URL 规则绑定 PUT 方法
     * @param $urlRule
     * @return $this
     */
    public function put($urlRule)
    {
        $this->map([self::METHOD_PUT], $urlRule);
        return $this;
    }

    /**
     * 为 URL 规则绑定 DELETE 方法
     * @param $urlRule
     * @return $this
     */
    public function delete($urlRule)
    {
        $this->map([self::METHOD_DELETE], $urlRule);
        return $this;
    }

    /**
     * 匹配 URL 是否符合这个路由规则
     * @param string $url 待匹配 URL
     * @return bool
     */
    public function matchUrl($url)
    {
        $matches = [];
        if (preg_match($this->regexUrlPattern, $url, $matches)) {
            $this->url = $url;
            //删掉全局匹配(这里等价于完整字符串)
            array_shift($matches);
            $this->parts = $matches;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据 parts 生成 URL
     *
     * @param array $parts
     * @return string
     */
    public function generatePath(array $parts)
    {
        $template = str_replace(['[', ']'], ['', ''], $this->urlRule);
        $keys = [];
        $values = [];
        foreach ($parts as $k => $v) {
            $keys[] = '{' . $k . '}';
            $values[] = $v;
        }
        return str_replace($keys, $values, $template);
    }

    /**
     * 断言 URL 规则合法
     * @param $urlRule
     * @throws RouteException
     */
    private function assertUrlRule($urlRule)
    {
        if (empty($urlRule) or !is_string($urlRule)) {
            throw new RouteException('URL 规则不合法');
        }
    }

    /**
     * 将 urlRule 的语法翻译成正则表达式
     */
    private function setupRegexRule()
    {
        $urlRule = $this->urlRule;
        $regexUrlPattern = str_replace(['[', ']', '/'], ['(?:', ')?', '\/'], $urlRule);
        $regexUrlPattern = preg_replace('/\{(\S+?)\}/', '(?<$1>\S+?)', $regexUrlPattern);
        $regexUrlPattern = '/^' . $regexUrlPattern . '$/';
        $this->regexUrlPattern = $regexUrlPattern;
    }


}