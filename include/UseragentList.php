<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 08.01.15
 * Time: 10:39
 */

namespace Grabber;

require_once('Store.php');

class UseragentList {
    static function get() {
        $agents = self::load_file();
        $ridx = rand(0, count($agents)-1);
        $agent = $agents[$ridx];
        return $agent;
    }

    static  function load_file() {
        $agents = array();

        $file_name = self::get_ua_file();
        $data = file_get_contents($file_name);

        $lines = explode("\n", $data);

        foreach($lines as $line) {
            $line = trim($line);

            // skip empty
            if (empty($line)) {
                continue;
            }

            // skip commented
            if (substr($line, 0, 1) == '#') {
                continue;
            }

            // parse host:port format
            $agents[] = trim($line);
        }

        return $agents;
    }

    static function get_ua_file() {
        //$store = new \Grabber\Store('public://grabber/useragent');
        //return $store->get_file_name('useragent.lst');
        return __DIR__ . '/Useragents/useragent.lst';
    }
}
