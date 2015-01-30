<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 13.11.14
 * Time: 13:34
 */

namespace Grabber\Processors;

require_once(__DIR__ . '/../Registry.php');
require_once(__DIR__ . '/../Event.php');
require_once(__DIR__ . '/../Store.php');
require_once(__DIR__ . '/../Cache.php');
require_once(__DIR__ . '/../NodeHelper.php');
require_once(__DIR__ . '/../ProcessHelper.php');
require_once(__DIR__ . '/../Logger.php');

use Grabber\Cache;
use Grabber\EventFactory;
use Grabber\NodeHelper;
use Grabber\Registry;
use Grabber\ProcessHelper;
use Grabber\Logger;

class Filler
{
    function execute() {
        $helpers = $this->get_helpers();

        $queue = Registry::get_queue();
        $events = $queue->get_optimized($queue);

        foreach($events as $event) {
            // time tracking
            Registry::get_tracker()->execute();

            // merge
            $is_filled = $this->fill($helpers, $event['data']);

            if ($is_filled || empty($helpers)) {
                $new_event = EventFactory::create_filled(get_class(), $event['data']);
                $queue->add($new_event);
                $queue->remove($event);
            }
        }

        ProcessHelper::execute_processor("Prefetcher");
    }

    function get_helpers() {
        $dir = __DIR__ . '/../Fillers';
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
            $class = trim($class, '0123456789. ');
            $class = ucfirst($class);
            $class = '\\Grabber\\Fillers\\' . $class;
            $helpers[] = new $class();
        }

        return $helpers;
    }

    function fill(array $helpers, array &$item) {
        $is_filled = FALSE;
        $required_fields = NodeHelper::get_node_fields($item['node_type']);

        foreach($helpers as $helper) {
            $helper_class = get_class($helper);

            try {
                $cache_key = md5($item['title']);
                $cached = Cache::get($cache_key); // FIXME use FileCache

                if (empty($cached)) {
                    $data_helper = new $helper_class();
                    $data = $data_helper->execute($item);
                    Cache::set($cache_key, $data);
                } else {
                    $data = $cached;
                }

                // merge fields
                foreach($required_fields as $field) {
                    if (!empty($item[$field])) {
                        continue;
                    }

                    if (empty($data[$field])) {
                        continue;
                    }

                    $item[$field] = $data[$field];
                }

                $is_filled = TRUE;
                break; // only one helper use

            } catch (\Exception $e) {
                Logger::error("Not fount in {$helper_class}: " . $item['title']);
            }
        }

        return $is_filled;
    }
}

