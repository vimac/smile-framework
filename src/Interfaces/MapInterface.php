<?php
/**
 * Created by IntelliJ IDEA.
 * User: mac
 * Date: 06/05/2017
 * Time: 12:31
 */

namespace Smile\Interfaces;

use ArrayAccess;

interface MapInterface extends ArrayAccess
{
    public function get($key, $defaultValue = null);

    public function getAll();

    public function keys();

    public function has($key);

    public function set($key, $value);

    public function remove($key);

    public function clear();
}