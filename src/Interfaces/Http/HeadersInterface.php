<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Smile\Interfaces\Http;


use Smile\Interfaces\MapInterface;

interface HeadersInterface extends MapInterface
{
    public function add($key, $value);

    public function normalizeKey($key);
}
