<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 20.09.14
 * Time: 19:43
 */

namespace Grabber;

require_once 'MemCache.php';
require_once 'DBCache.php';


class Cache
{
    static function get($id, $default=NULL) {
        $data = MemCache::get($id, $default);

        if ($data) {
            return $data;
        }

        $data = DBCache::get($id);

        if ($data) {
            return $data;
        }

        return $default;
    }

    static function set($id, $value) {
        MemCache::set($id, $value);
        DBCache::set($id, $value);
    }
}


