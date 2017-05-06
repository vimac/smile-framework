<?php


namespace Smile;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Smile\Di\Container;
use Smile\Di\ElementDefinition;
use Smile\Interfaces\ContainerInterface;
use Smile\Interfaces\DispatcherInterface;
use Smile\Interfaces\RouterInterface;
use Smile\Router\Router;

class Application
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * 应用构造方法, 这个方法接受两个参数, 分别是应用初始化容器的方法和框架默认初始化容器的Provider
     *
     * @param callable|null $containerLoader
     * @param string $provider Provider的类名
     */
    public function __construct(callable $containerLoader = null, $provider = ContainerProvider::class)
    {
        //初始化容器, 并把容器自身的引用放入容器中
        $container = new Container();
        $container->set(
            (new ElementDefinition())
                ->setType(ContainerInterface::class)
                ->setInstance($container)
                ->setAlias('container')
        );

        //初始化ContainerProvider
        /** @var ContainerProvider $providerInstance */
        $providerInstance = new $provider;
        $container->set(
            (new ElementDefinition())
                ->setType($provider)
                ->setInstance($providerInstance)
        );
        $providerInstance->setupContainer($container);

        if (isset($containerLoader)) {
            call_user_func($containerLoader, $container);
        }

        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function loadRouterConfig(callable $routerLoader = null)
    {
        /** @var Router $router */
        $router = $this->container->getByAlias('router');

        if (isset($routerLoader)) {
            call_user_func($routerLoader, $router);
        }
    }

    public function run()
    {
        /** @var ServerRequestInterface $request */
        $request = $this->container->getByAlias('request');

        /** @var ResponseInterface $response */
        $response = $this->container->getByAlias('response');

        /** @var RouterInterface $router */
        $router = $this->container->getByAlias('router');

        $route = $router->resolve($request->getMethod(), $request->getUri()->getPath());

        /** @var DispatcherInterface $dispatcher */
        $dispatcher = $this->container->getByAlias('dispatcher');

        $callable = $dispatcher->dispatch($route, $request);

        $newResponse = call_user_func($callable, $request, $response);

        $this->respond($newResponse);
    }

    /**
     * 把Response返回到客户端
     *
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {
        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $chunkSize = 1024; //暂时写死

            $contentLength = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }


            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * 获取返回 Response 是否为空
     *
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }
}