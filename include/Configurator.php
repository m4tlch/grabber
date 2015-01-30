<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 29.10.14
 * Time: 11:05
 */

namespace Grabber;

require_once('Settings.php');

class Configurator {
    static function execute(&$object, array $groups=array()) {
        $class = get_class($object);

        $properties = (array)$object;

        foreach($properties as $property=>$value) {
            $stored_value = self::get_property_value($class, $groups, $property);

            if (!is_null($stored_value)) {
                $object->{$property} = $stored_value;
            }
        }
    }

    static function get_settings_panel($object) {
        $class = get_class($object);
        $settings_class = $class . 'Settings';

        if (!class_exists($settings_class)) {
            throw new \Exception('No settings for ' . $class);
        }

        $settings_panel = new $settings_class($object);

        return $settings_panel;
    }

    static function set_property_value($class, $groups, $property, $value) {
        Settings::set($class, $groups, $property, $value);
    }

    static function get_property_value($class, $groups, $property) {
        return Settings::get($class, $groups, $property);
    }

    static function get_response_ok($data=NULL) {
        $response = array('ok' => TRUE, 'data' => $data);
        return json_encode($response);
    }

    static function get_response_fail($data=NULL) {
        $response = array('ok' => FALSE, 'data' => $data);
        return json_encode($response);
    }
}
