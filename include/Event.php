<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 13.11.14
 * Time: 18:12
 */

namespace Grabber;

class Event
{
    const TYPE_SCANNED = 'SCANNED';
    const TYPE_OPTIMIZED = 'OPTIMIZED';
    const TYPE_FILLED = 'FILLED';
    const TYPE_IMAGES_FETCHED = 'IMAGES_FETCHED';
    const TYPE_LOADED = 'LOADED';
    const TYPE_URL = 'URL';
}

class EventFactory {
    static function create($type, $source, $data=NULL) {
        $event = array(
            'type' => $type,
            'source' => $source,
            'data' => $data,
        );
        return $event;
    }

    static function create_scanned($source, $data=NULL) {
        $event = self::create(Event::TYPE_SCANNED, $source, $data);
        return $event;
    }

    static function create_optimized($source, $data=NULL) {
        $event = self::create(Event::TYPE_OPTIMIZED, $source, $data);
        return $event;
    }

    static function create_filled($source, $data=NULL) {
        $event = self::create(Event::TYPE_FILLED, $source, $data);
        return $event;
    }

    static function create_images_fetched($source, $data=NULL) {
        $event = self::create(Event::TYPE_IMAGES_FETCHED, $source, $data);
        return $event;
    }

    static function create_loaded($source, $data=NULL) {
        $event = self::create(Event::TYPE_LOADED, $source, $data);
        return $event;
    }
}
