<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 20.09.14
 * Time: 19:43
 */

namespace Grabber;


class MemCache
{
    static $cache=array();

    static function get($id, $default=NULL) {
        if (isset(self::$cache[$id])) {
            return self::$cache[$id];
        }

        return $default;
    }

    static function set($id, $value) {
        self::$cache[$id] = $value;
    }
}


