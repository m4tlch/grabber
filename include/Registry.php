<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 14.11.14
 * Time: 11:21
 */

namespace Grabber;

class Registry {
    static protected $queue = NULL;
    static protected $event_store = NULL;
    static protected $scanner_store = NULL;
    static protected $file_cache_store = NULL;
    static protected $semaphores_store = NULL;
    static protected $image_fetcher = NULL;
    static protected $scheduler = NULL;
    static protected $semaphore = NULL;
    static protected $processors = array();
    static protected $scanners = array();
    static protected $tracker = NULL;
    static protected $collector = NULL;

    static function &get_queue() {
        if (!self::$queue) {
            require_once('Queue.php');
            self::$queue = new Queue();
        }

        return self::$queue;
    }

    static function &get_event_store() {
        if (!self::$event_store) {
            require_once('Store.php');
            self::$event_store = new Store('public://grabber/events');
        }

        return self::$event_store;
    }

    static function &get_scanner_store() {
        if (!self::$scanner_store) {
            require_once('Store.php');
            self::$scanner_store = new Store('public://grabber/scanned');
        }

        return self::$scanner_store;
    }

    static function &get_file_cache_store() {
        if (!self::$file_cache_store) {
            require_once('Store.php');
            self::$file_cache_store = new Store('public://grabber/cache');
        }

        return self::$file_cache_store;
    }

    static function &get_semaphores_store() {
        if (!self::$semaphores_store) {
            require_once('Store.php');
            self::$semaphores_store = new Store('public://grabber/semaphores');
        }

        return self::$semaphores_store;
    }

    static function get_image_fetcher() {
        if (!self::$image_fetcher) {
            require_once('ImageFetcher.php');
            self::$image_fetcher = new ImageFetcher();
        }

        return self::$image_fetcher;
    }

    static function get_scheduler() {
        if (!self::$scheduler) {
            require_once('Scheduler.php');
            self::$scheduler = new Scheduler();
        }

        return self::$scheduler;
    }

    static function get_semaphore() {
        if (!self::$semaphore) {
            require_once('Semaphore.php');
            self::$semaphore = new Semaphore();
        }

        return self::$semaphore;
    }

    static function get_processor($name) {
        $name = ucfirst($name);

        if (empty(self::$processors[$name])) {
            require_once(__DIR__ . "/Processors/{$name}.php");
            $class = "\\Grabber\\Processors\\{$name}";
            self::$processors[$name] = new $class();
        }

        return self::$processors[$name];
    }

    static function get_scanner($name) {
        $name = ucfirst($name);

        if (empty(self::$scanners[$name])) {
            require_once(__DIR__ . "/Scanners/{$name}.php");
            $class = "\\Grabber\\Scanners\\{$name}";
            self::$scanners[$name] = new $class();
        }

        return self::$scanners[$name];
    }

    static function get_tracker() {
        if (empty(self::$tracker)) {
            require_once(__DIR__ . "/Tracker.php");
            self::$tracker = new Tracker();
        }

        return self::$tracker;
    }

    static function get_collector() {
        if (empty(self::$collector)) {
            require_once(__DIR__ . "/Processors/Collector.php");
            self::$collector = new \Grabber\Processors\Collector();
        }

        return self::$collector;
    }
}
