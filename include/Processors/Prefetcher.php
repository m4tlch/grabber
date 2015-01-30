<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 15.11.14
 * Time: 15:55
 */

namespace Grabber\Processors;

require_once(__DIR__ . '/../Registry.php');
require_once(__DIR__ . '/../Event.php');
require_once(__DIR__ . '/../NodeHelper.php');
require_once(__DIR__ . '/../ProcessHelper.php');
require_once(__DIR__ . '/../Logger.php');

use Grabber\EventFactory;
use Grabber\NodeHelper;
use Grabber\Registry;
use Grabber\ProcessHelper;
use Grabber\Logger;

class Prefetcher {
    function execute() {
        $queue = Registry::get_queue();
        $events = $queue->get_filled();

        foreach($events as $event) {
            // time tracking
            Registry::get_tracker()->execute();

            // fetch
            $this->fetch($event['data']);

            $new_event = EventFactory::create_images_fetched(get_class(), $event['data']);
            $queue->add($new_event);
            $queue->remove($event);
        }

        ProcessHelper::execute_processor("Loader");
    }

    function fetch($data) {
        $image_fields = NodeHelper::get_image_fields($data['node_type']);
        $image_fetcher = Registry::get_image_fetcher();

        foreach($image_fields as $field) {
            if (empty($data[$field])) {
                continue;
            }

            $urls = is_array($data[$field]) ? $data[$field] : array($data[$field]);

            foreach($urls as $url) {
                $image_fetcher->execute($url);
            }
        }
    }
}

