<?php


namespace Smile\Controller;


use Smile\Interfaces\ContainerInterface;

abstract class BaseController
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

}