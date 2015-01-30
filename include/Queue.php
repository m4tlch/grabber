<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 14.11.14
 * Time: 11:03
 */

namespace Grabber;

require_once("Registry.php");
require_once("Event.php");

class Queue
{
    var $store = null;

    function __construct($store=NULL) {
        $this->store = $store ? $store : Registry::get_event_store();
    }

    function add(array $event) {
        $this->store->write_event($event);
    }

    function get() {
        $event = $this->store->get_event();
        return $event;
    }

    function remove(array $event) {
        $this->store->backup($event);
    }

    function get_scanned() {
        return $this->store->get_events(Event::TYPE_SCANNED);
    }

    function get_optimized() {
        return $this->store->get_events(Event::TYPE_OPTIMIZED);
    }

    function get_filled() {
        return $this->store->get_events(Event::TYPE_FILLED);
    }

    function get_images_fetched() {
        return $this->store->get_events(Event::TYPE_IMAGES_FETCHED);
    }

    function get_loaded() {
        return $this->store->get_events(Event::TYPE_LOADED);
    }

    function get_urls($source) {
        $events = $this->store->get_events(Event::TYPE_URL);

        $result = array();

        foreach($events as $event) {
            if ($event['source'] == $source) {
                $result[] = $event;
            }
        }

        return $result;
    }

    function get_custom_event($event_type) {
        return $this->store->get_events($event_type);
    }
}

/*
class QueueFactory {
    static function get() {
        return Queue::get_instance();
    }
}
*/
