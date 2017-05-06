<?php


namespace Smile\Test\Router;


use PHPUnit\Framework\TestCase;
use Smile\Router\Route;
use Smile\Router\Router;

class RouterTest extends TestCase
{
    public function routerProvider()
    {
        $router = new Router;
        $route1 = new Route;
        $route1->get('/a/{b}/{c}')
            ->setTarget(function () {
            })
            ->setName('abc');
        $route2 = new Route;
        $route2->post('/')
            ->setTarget(function () {
            })
            ->setName('slash');
        $route3 = new Route;
        $route3->restful('/a/[{b}]')
            ->setTarget(function () {
            })
            ->setName('ab');
        $router->addRoute($route1);
        $router->addRoute($route2);
        $router->addRoute($route3);

        return [
            [$router, $route1, $route2, $route3]
        ];
    }

    /**
     * @dataProvider routerProvider
     * @param Router $router
     * @param Route $route1
     * @param Route $route2
     * @param Route $route3
     */
    public function testRouter1(Router $router, Route $route1, Route $route2, Route $route3)
    {
        $route = $router->resolve('get', '/a/hello/world');
        $this->assertSame($route1, $route);
        $this->assertEquals('hello', $route->getParts()->get('b'));
    }

    /**
     * @dataProvider routerProvider
     * @param Router $router
     * @param Route $route1
     * @param Route $route2
     * @param Route $route3
     */
    public function testRouter2(Router $router, Route $route1, Route $route2, Route $route3)
    {
        $route = $router->resolve('post', '/');
        $this->assertSame($route2, $route);
    }

    /**
     * @dataProvider routerProvider
     * @param Router $router
     * @param Route $route1
     * @param Route $route2
     * @param Route $route3
     */
    public function testRouter3(Router $router, Route $route1, Route $route2, Route $route3)
    {
        $route = $router->resolve('GET', '/a/index');
        $this->assertSame($route3, $route);
    }


    /**
     * @dataProvider routerProvider
     * @param Router $router
     * @param Route $route1
     * @param Route $route2
     * @param Route $route3
     */
    public function testGeneratePath(Router $router, Route $route1, Route $route2, Route $route3)
    {
        $path = $router->generatePath('abc', ['b' => 'hello']);
        $this->assertEquals('/a/hello/{c}', $path);

        $path = $router->generatePath('abc', ['b' => 'hello', 'c' => 'world']);
        $this->assertEquals('/a/hello/world', $path);

        $path = $router->generatePath('ab', ['b' => 'hello']);
        $this->assertEquals('/a/hello', $path);

        $path = $router->generatePath('ab');
        $this->assertEquals('/a/{b}', $path);
    }
}