<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 08.01.15
 * Time: 10:39
 */

namespace Grabber;

require_once('Store.php');
require_once('Registry.php');
require_once('Snoopy.class.php');
require_once('simple_html_dom.inc.php');

class ProxyList {
    const MIN_GOOD_PROXIES = 7;

    static function get() {
        // load lists
        $proxies = self::load_file();
        $blacklist = self::load_blacklist();

        // remove bad proxies
        $good_proxies = array_diff($proxies, $blacklist);

        if (count($good_proxies) <= self::MIN_GOOD_PROXIES) {
            $semaphore = Registry::get_semaphore();

            if ($semaphore->is_set('ProxyList-update')) {
                sleep(5);
            } else {
                $semaphore->set('ProxyList-update');
                self::update_file();
                $semaphore->delete('ProxyList-update');
            }

            $proxies = self::load_file();
            $good_proxies = array_diff($proxies, $blacklist);
        }

        if (empty($good_proxies)) {
            throw new \Exception("No good proxies. list/blacklisted: " . count($proxies) . "/" . count($blacklist));
        }

        // reset keys
        $good_proxies = array_values($good_proxies);

        // random
        $ridx = rand(0, count($good_proxies)-1);
        $line = $good_proxies[$ridx];

        // parse host:port format
        $exploded_line = explode(':', $line);

        $host = $exploded_line[0];
        $port = !empty($exploded_line[1]) ? $exploded_line[1] : 0;

        $proxy = array(
            'host' => trim($host),
            'port' => trim($port),
        );

        return $proxy;
    }

    static function load_file() {
        $proxies = array();

        $file_name = self::get_proxy_file();

        if (!file_exists($file_name)) {
            return array();
        }

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

            $proxies[] = $line;
        }

        return $proxies;
    }

    static function load_blacklist() {
        $blacklist = array();

        $file_name = self::get_blacklist_file();

        if (!file_exists($file_name)) {
            return array();
        }

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

            $blacklist[] = $line;
        }

        return $blacklist;
    }

    static function get_proxy_file() {
        $store = new \Grabber\Store('public://grabber/proxy');
        return $store->get_file_name('proxy.lst');
    }

    static function get_blacklist_file() {
        $store = new \Grabber\Store('public://grabber/proxy');
        return $store->get_file_name('proxy.blacklist.lst');
    }

    static function update_file() {
        $helpers = self::get_helpers();

        if (empty($helpers)) {
            return;
        }

        $blacklist = self::load_blacklist();

        foreach($helpers as $helper) {
            $proxies = $helper::execute();

            // skip empty
            if (!$proxies) {
                continue;
            }

            // remove blacklisted
            $good_proxies = array_diff($proxies, $blacklist);

            // save if exist valid
            if (count($good_proxies) > self::MIN_GOOD_PROXIES) {
                $filename = self::get_proxy_file();
                $data = implode("\n", $good_proxies) . "\n";
                file_put_contents($filename, $data);

                // only one source
                Logger::log("Updated Proxy list. From: " . get_class($helper) . ". New/good/blacklisted: " . count($proxies) . "/" . count($good_proxies) . "/" . count($blacklist));
                break;

            } else {
                Logger::log("No good proxies from: " . get_class($helper) . ". New/good/blacklisted: " . count($proxies) . "/" . count($good_proxies) . "/" . count($blacklist));
            }
        }
    }

    static function add_to_blacklist($host, $port) {
        $blacklist = self::load_blacklist();
        $blacklist[] = "{$host}:{$port}";
        $blacklist = array_unique($blacklist);
        self::save_blacklist($blacklist);
    }

    static function save_blacklist($blacklist) {
        $data = implode("\n", $blacklist) . "\n";
        $filename = self::get_blacklist_file();
        file_put_contents($filename, $data);
    }

    static function get_helpers() {
        $dir = __DIR__ . '/ProxyLists';
        $matches = array();
        $helpers = array();
        $filtered = array();

        $files = scandir($dir);

        foreach($files as $file) {
            if ($file == '..' || $file =='.') continue;
            if (!preg_match('/\.php$/', $file, $matches)) continue;
            if (is_dir($dir . '/' . $file)) continue;
            $filtered[] = $file;
        }

        sort($filtered);

        foreach($filtered as $file) {
            include_once($dir . '/' . $file);
            $class = str_replace(".php", "", $file);
            $class = trim($class, '0123456789.- ');
            $class = ucfirst($class);
            $class = '\\Grabber\\ProxyLists\\' . $class;
            $helpers[] = new $class();
        }

        return $helpers;
    }
}
