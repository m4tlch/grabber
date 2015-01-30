<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 11.10.14
 * Time: 17:17
 */

namespace Grabber;

require_once('NodeHelper.php');

class Settings {
    var $data = array();
    var $object = NULL;

    function __construct($object) {
        $this->object = $object;
        // $class = get_class($object);
    }

    static function get($class, array $conditions, $property) {
        $key = self::build_key($class, $conditions, $property);

        $settings = SettingsStore::get($key);

        return $settings;
    }

    static function set($class, array $conditions, $property, $value) {
        $key = self::build_key($class, $conditions, $property);

        SettingsStore::set($key, $value);
    }

    static function build_key($class, array $conditions, $property) {
        return $class . '?' . http_build_query($conditions) . '.' . $property;
    }
}


class SettingsStore
{
    static function get($key) {
        $result = variable_get($key);
        return $result;
    }

    static function set($key, $value) {
        variable_set($key, $value);
    }
}

