<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 18.10.14
 * Time: 12:05
 */

namespace Grabber;


class DBCache {
    static function get($id) {
        $cached = cache_get($id);

        if ($cached !== FALSE) {
            return $cached->data;
        } else {
            return FALSE;
        }
    }

    static function set($id, $data) {
        return cache_set($id, $data);
    }
}
