<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 16.11.14
 * Time: 10:14
 */

namespace Grabber;

require_once("Registry.php");

class FileCache {
    static function set($key, $data) {
        $store = Registry::get_file_cache_store();
        $store->write_file($key, $data);
    }

    static function get($key) {
        $store = Registry::get_file_cache_store();
        return $store->get_file($key);
    }

    static function is_exists($key) {
        $store = Registry::get_file_cache_store();
        return $store->is_file_exists($key);
    }

    static function get_file_name($key) {
        $store = Registry::get_file_cache_store();
        return $store->get_file_name($key);
    }
}
