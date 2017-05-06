<?php


namespace Smile\Test\Router;


use PHPUnit\Framework\TestCase;
use Smile\Router\Route;

class RouteTest extends TestCase
{

    public function testSimpleRoute()
    {
        $route = new Route;
        $route->get('/a/b/c');

        $this->assertTrue($route->matchUrl('/a/b/c'));
        $this->assertFalse($route->matchUrl('/a/b'));
        $this->assertFalse($route->matchUrl('/a/b/c/d'));
        $this->assertFalse($route->matchUrl('/a/b/c/'));
        $this->assertFalse($route->matchUrl('/b/c/'));
        $this->assertFalse($route->matchUrl(''));
    }

    public function testMatchRoute()
    {
        $route = new Route;
        $route->post('/a/{b}/c');

        $this->assertTrue($route->matchUrl('/a/hello/c'));
        $this->assertArrayHasKey('b', $route->getParts());
        $this->assertEquals('hello', $route->getParts()['b']);

        $this->assertFalse($route->matchUrl('/a/b'));
        $this->assertFalse($route->matchUrl(''));
    }

    public function testOptionalMatchRoute()
    {
        $route = new Route;
        $route->put('/a[/{b}[/{c}]]');
        $route->matchUrl('/a/hello/world');

        $this->assertArrayHasKey('b', $route->getParts());
        $this->assertEquals('hello', $route->getParts()['b']);

        $this->assertArrayHasKey('c', $route->getParts());
        $this->assertEquals('world', $route->getParts()['c']);

        $route = new Route;
        $route->put('/a[/{b}[/{c}]]');
        $route->matchUrl('/a/hello');

        $this->assertArrayHasKey('b', $route->getParts());
        $this->assertEquals('hello', $route->getParts()['b']);

        $this->assertArrayNotHasKey('c', $route->getParts());
    }

}