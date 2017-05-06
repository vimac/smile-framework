<?php


namespace Smile\Test;


use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smile\Application;
use Smile\Common\Environment;
use Smile\Controller\BaseController;
use Smile\Interfaces\ContainerInterface;
use Smile\Interfaces\RouterInterface;
use Smile\Router\Route;

class ApplicationTest extends TestCase
{
    public function initializeProvider()
    {
        return [
            [
                function (ContainerInterface $container) {
                    $container->enableAutowiredForNamespace(__NAMESPACE__);
                },
                function (RouterInterface $router) {
                    $router->addRoute(
                        (new Route)
                            ->put('/{a}[/{b}[/{c}]]')
                            ->setTarget(TestController::class)
                    );
                }
            ]
        ];
    }

    /**
     * @dataProvider initializeProvider
     * @param callable $containerInit
     * @param callable $routerInit
     */
    public function testApplication(callable $containerInit, callable $routerInit)
    {
        $application = new Application($containerInit);
        /** @var Environment $environment */
        $environment = $application->getContainer()->getByAlias('environment');
        $environment->replace([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/foo/bar/test',
            'QUERY_STRING' => 'abc=123&foo=bar',
            'SERVER_NAME' => 'example.com',
            'CONTENT_TYPE' => 'application/json;charset=utf8',
            'CONTENT_LENGTH' => 15
        ]);
        $application->loadRouterConfig($routerInit);
        $application->run();
    }

}

class TestController extends BaseController
{
    public function test(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withJson($request->getQueryParams());
    }
}

