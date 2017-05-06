<?php


namespace Smile;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smile\Common\Environment;
use Smile\Di\ElementDefinition;
use Smile\Dispatcher\Dispatcher;
use Smile\Http\Headers;
use Smile\Http\Request;
use Smile\Http\Response;
use Smile\Interfaces\ContainerInterface;
use Smile\Interfaces\ContainerProviderInterface;
use Smile\Interfaces\DispatcherInterface;
use Smile\Interfaces\RouterInterface;
use Smile\Router\Router;

/**
 * 框架默认的容器Provider
 *
 * @package Smile
 */
class ContainerProvider implements ContainerProviderInterface
{

    /**
     * 容器初始化Provider
     *
     * @param ContainerInterface $container
     */
    public function setupContainer(ContainerInterface $container)
    {
        $container->set(
            (new ElementDefinition())
                ->setType(Environment::class)
                ->setBuilder(function () {
                    return new Environment($_SERVER);
                })
                ->setSingletonScope()
                ->setAlias('environment')
        );
        $container->set(
            (new ElementDefinition())
                ->setType(ServerRequestInterface::class)
                ->setBuilder(function (Environment $environment) {
                    return Request::createFromEnvironment($environment);
                })
                ->setSingletonScope()
                ->setAlias('request')
        );
        $container->set(
            (new ElementDefinition())
                ->setType(ResponseInterface::class)
                ->setBuilder(function () {
                    $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
                    $response = new Response(200, $headers);
                    return $response;
                })
                ->setSingletonScope()
                ->setAlias('response')
        );
        $container->set(
            (new ElementDefinition())
                ->setType(RouterInterface::class)
                ->setBuilder(function () {
                    return new Router();
                })
                ->setSingletonScope()
                ->setAlias('router')
        );
        $container->set(
            (new ElementDefinition())
                ->setType(DispatcherInterface::class)
                ->setBuilder(function (ContainerInterface $container) {
                    return new Dispatcher($container);
                })
                ->setSingletonScope()
                ->setAlias('dispatcher')
        );
    }

}