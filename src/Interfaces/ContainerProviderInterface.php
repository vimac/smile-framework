<?php
/**
 * Created by IntelliJ IDEA.
 * User: mac
 * Date: 06/05/2017
 * Time: 17:40
 */

namespace Smile\Interfaces;

interface ContainerProviderInterface
{
    /**
     * 容器初始化Provider
     *
     * @param ContainerInterface $container
     */
    public function setupContainer(ContainerInterface $container);
}