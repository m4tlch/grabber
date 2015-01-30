<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 20.09.14
 * Time: 21:17
 */

namespace Grabber;


require_once('Registry.php');
require_once('Event.php');


/**
 * Class Scanner
 * @package Grabber
 */
class Scanner
{
    /* @var array Event */
    var $getted_url_event = NULL;

    function add_url($url, $data=array()) {
        $queue = Registry::get_queue();
        $event = EventFactory::create(Event::TYPE_URL, get_called_class(), $data);
        $event['url'] = $url;
        $queue->add($event);
    }

    function get_url() {
        $queue = Registry::get_queue();
        $events = $queue->get_urls(get_called_class());

        if (empty($events)) {
            return NULL;
        }

        $random_index = rand(0, count($events)-1);
        $event = $events[$random_index];

        $this->getted_url_event = $event;

        $url = !empty($event['url']) ? $event['url'] : $event['data']['url'];
        $data = $event['data'];

        return array('url' => $url, 'data' => $data);
    }

    function remove_url($url) {
        $queue = Registry::get_queue();
        $queue->remove($this->getted_url_event);
        $this->getted_url_event = NULL;
    }

    /**
     * @return array
     */
    function execute() {
        return array();
    }

    function emit($data, $event_type=Event::TYPE_SCANNED) {
        $event = EventFactory::create($event_type, get_called_class(), $data);
        Registry::get_queue()->add($event);
    }
}

