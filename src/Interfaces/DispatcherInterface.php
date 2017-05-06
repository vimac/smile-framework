<?php
/**
 * Created by IntelliJ IDEA.
 * User: mac
 * Date: 06/05/2017
 * Time: 16:39
 */

namespace Smile\Interfaces;


use Psr\Http\Message\ServerRequestInterface;
use Smile\Router\Route;

interface DispatcherInterface
{
    public function dispatch(Route $route, ServerRequestInterface $request);
}