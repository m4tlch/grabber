<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 05.10.14
 * Time: 15:28
 */

namespace Grabber;


class Logger {
    const TYPE_INFO='info';
    const TYPE_ERROR='error';
    static $errors = array();
    static $session_id = NULL;

    static function log($message, array $data=array()) {
        self::save(self::TYPE_INFO, $message, $data);
    }

    static function error($message, array $data=array()) {
        self::save(self::TYPE_ERROR, $message, $data);
    }

    static function save($type, $message, array $data=array()) {
        //self::$errors[] = array($type => $message);
        $record = array(
            'sid' => self::get_session_id(),
            'created' => time(),
            'type' => $type,
            'message' => $message,
        );

        if (isset($data['nid'])) {
            $record['nid'] = $data['nid'];
        }

        if (isset($data['grabber'])) {
            $record['grabber'] = $data['grabber'];
        }

        db_insert('grabber_log')
            ->fields($record)
            ->execute();

    }

    static function get_session_id() {
        if (is_null(self::$session_id)) {
            self::$session_id = time();
        }
        return self::$session_id;
    }

    static function delete_old() {
        // Expire old log entries.
        db_delete('grabber_log')
            ->condition('created', REQUEST_TIME - 604800, '<')
            ->execute();
    }

    static function get_errors_count() {
        // Expire old log entries.
        $data = db_select('grabber_log')
            ->fields('grabber_log', array('id'))
            ->condition('type', self::TYPE_ERROR)
            ->countQuery()
            ->execute();

        return $data->fetchField();
    }

    static function clear() {
        // Expire old log entries.
        db_delete('grabber_log')->execute();
    }
}
