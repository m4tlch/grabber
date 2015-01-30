<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 13.09.14
 * Time: 11:28
 */

namespace Grabber;

require_once('Logger.php');

/**
 * Class Store
 * @package Grabber
 */
class Store {
    const BACKUP_FOLDER = 'ok';
    const EVENT_FILE = 'event.json';

    var $path = '/tmp/grabber';

    function __construct($uri) {
        $this->set_path($uri);
    }

    function set_path($uri) {
        if ($wrapper = file_stream_wrapper_get_instance_by_uri($uri)) {
            $this->path = $wrapper->realpath();

            if ($wrapper = file_stream_wrapper_get_instance_by_uri('public://')) {
                $path = $wrapper->realpath();
                $this->make_dir($path . '/grabber');

                $wrapper = file_stream_wrapper_get_instance_by_uri($uri);
                $this->path = $wrapper->realpath();
            }

            if (!file_exists($this->path)) {
                $this->make_dir($this->path);
            }
        }
    }

    /**
     * @param $dir
     */
    function make_dir($dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, TRUE);
        }
    }

    function write_event(array &$event) {
        $source = $event['source'];
        $source = str_replace('\\', '_', $source);
        $source = str_replace('/', '_', $source);

        $type = $event['type'];

        if (!isset($event['file'])) {
            $unique_name = microtime(true);
            $file = $this->path . "/{$type}/{$source}/{$unique_name}." . self::EVENT_FILE;

        } else {
            $file = $event['file'];
        }

        $dir = dirname($file);
        $this->make_dir($dir);

        $event['file'] = $file;
        $content = json_encode($event);

        $result = file_put_contents($file, $content);

        return $result;
    }

    function get_events($type) {
        $files = $this->scandir_for_events($type);
        $filtered = array();

        foreach($files as $file) {
            $content = @file_get_contents($file);

            if (!$content) {
                continue;
            }

            $event = json_decode($content, TRUE);
            $event['file'] = $file;

            if (!$event) {
                continue;
            }

            // skip malformed event
            if (empty($event['type'])) {
                continue;
            }

            if ($event['type'] == $type) {
                $filtered[] = $event;
            }
        }

        return $filtered;
    }

    function scandir_for_events($type)
    {
        $events = array();

        $items = glob($this->path . "/{$type}/*");

        // filter and scan subfolders
        for ($i = 0; $i < count($items); $i++) {
            // skip backup folders
            if (basename($items[$i]) == self::BACKUP_FOLDER) {
                continue;
            }

            // recursive
            if (is_dir($items[$i])) {
                $add = glob($items[$i] . '/*');
                $items = array_merge($items, $add);
                continue;
            };

            // skip non events
            if (strstr($items[$i], self::EVENT_FILE) == false) {
                continue;
            }

            $events[] = $items[$i];
        }

        return $events;
    }
/*
    function get($source, $folder_name) {
        $source = str_replace('\\', '_', $source);
        $file = $this->path . "/{$source}/{$folder_name}/" . self::EVENT_FILE;

        if (!file_exists($file)) {
            return FALSE;
        }

        return json_decode(file_get_contents($file), TRUE);
    }
*/
    function scandir_through($dir)
    {
        $items = glob($dir . '/*');

        for ($i = 0; $i < count($items); $i++) {
            if (is_dir($items[$i])) {
                if (basename($items[$i]) == self::BACKUP_FOLDER) {
                    continue;
                }
                $add = glob($items[$i] . '/*');
                $items = array_merge($items, $add);
            }
        }

        return $items;
    }

    function get_grabbed() {
        $dir = $this->path;
        $all = $this->scandir_through($dir);
        $items = array();

        // filter
        $filtered = array();

        foreach($all as $file) {
            if (strstr($file, self::EVENT_FILE) == false) {
                $filtered[] = $file;
            }
        }

        // load
        foreach($filtered as $file) {
            $item_folder = dirname($file);

            // item
            try {
                $item = $this->load_item($file);
            } catch (\Exception $e) {
                continue;
            }
/*
            // merge fetched
            $fetched_file = $item_folder . '/' . 'fetched.json';

            if (file_exists($fetched_file)) {
                try {
                    $fetched = $this->load_item($fetched_file);
                    $item = array_merge($item, $fetched);
                } catch (\Exception $e) {
                    // no fetched data
                    Logger::error($e->getMessage());
                }
            }
*/
//            $item['file'] = $item_folder;
            $items[] = $item;
        }

        return $items;
    }

    function load_item($item_file) {
        if (!file_exists($item_file))
            throw new \Exception("No file exists: {$item_file}");

        $json = file_get_contents($item_file);
        $item = json_decode($json, TRUE);

        return $item;
    }

    function backup($item) {
        $source = $item['file'];
        $destination = dirname($source) . '/' . self::BACKUP_FOLDER . '/' . basename($source);
        $this->make_dir(dirname($destination));
        rename($source, $destination);
    }

    function write_file($file_name, $content) {
        $file = $this->get_file_name($file_name);

        $dir = dirname($file);
        $this->make_dir($dir);

        @file_put_contents($file, $content);

        return $file;
    }

    function get_file($file_name) {
        $file = $this->get_file_name($file_name);
        return file_get_contents($file);
    }

    function is_file_exists($file_name) {
        $file = $this->get_file_name($file_name);
        return file_exists($file);
    }

    function clear() {
        $dir = $this->path;
        $all = $this->scandir_through($dir);

        // remove
        foreach($all as $dir) {
            $this->rrmdir($dir);
        }
    }

    function scandir_through_backup($dir)
    {
        $items = glob($dir . '/*');

        for ($i = 0; $i < count($items); $i++) {
            if (is_dir($items[$i])) {
                $add = glob($items[$i] . '/*');
                $items = array_merge($items, $add);
            }
        }

        return $items;
    }

    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object))
                        $this->rrmdir($dir."/".$object);
                    else
                        unlink($dir."/".$object);
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }

    function get_event() {
        $event = array();
        return $event;
    }

    function get_file_name($key) {
        $file = $this->path . "/{$key}";
        return $file;
    }
}
