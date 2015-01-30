<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 18.01.15
 * Time: 17:59
 */

namespace Grabber;

require_once('UserHelper.php');

use \Grabber\UserHelper;

class CommentHelper {
    static function create($entity, $comment_data) {
        // Get author
        if (!empty($comment_data['author'])) {
            $uid = UserHelper::get_author($comment_data['author']);

        } else {
            $uid = 0;
        }

        // parse date
        $created = self::parse_date($comment_data['created']);

        $d7_comment = (object)array(
            'nid' => $entity->nid,
            'cid' => 0,
            'pid' => 0,
            'uid' => $uid,
            'mail' => '',
            'is_anonymous' => 0,
            'homepage' => '',
            'status' => COMMENT_PUBLISHED,
            'subject' => t('(No subject)'),
            'language' => LANGUAGE_NONE,
            'date' => date('d-m-Y H:i:s', $created),
            'created' => $created,
            'changed' => $created,
        );

        return $d7_comment;
    }

    static function save($comment_object) {
        //comment_submit($comment_object);
        comment_save($comment_object);
    }

    static function find($nid, $text) {
        $query = new \EntityFieldQuery();
        $query
            ->entityCondition('entity_type', 'comment')
            ->propertyCondition('nid', $nid)
            ->fieldCondition('comment_body', 'value', $text);
        $result = $query->execute();

        if (!isset($result['comment'])) {
            return NULL;
        }

        // load first
        $cids = array_keys($result['comment']);
        $comment_entity = comment_load($cids[0]);

        return $comment_entity;
    }

    static function parse_date($date) {
        $created =  NULL;

        if (!empty($date)) {
            if (is_string($date)) {
                // 1. php date string parser
                $created = strtotime($date);

                // 2. custom date parser
                if (!$created) {
                    $created = self::parse_date_custom($date);
                    $created = $created ? $created : time();
                }
            } else {
                $created = is_numeric($date) ? $date : time();
            }

        } else {
            $created = time();
        }

        return $created;
    }

    static function parse_date_custom($date) {
        // '5 апреля 2013 года в 20:12'
        $monthes = array(
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        );
        $exploded = explode(' ', trim($date));

        if (count($exploded) != 6) {
            $timestamp = time();
            return $timestamp;
        }

        $day = $exploded[0];
        $month_name = $exploded[1];
        $month = $monthes[$month_name];
        $year = $exploded[2];
        $time = $exploded[5];
        list($hour, $min) = explode(':', $time);

        $timestamp = mktime($hour, $min, 0, $month, $day, $year);

        return $timestamp;
    }

    static function get_info($entity_type) {
        $info = field_info_instances('comment', 'comment_node_' . $entity_type);

        //$field_types = field_info_field_types();
        //$widget_types = field_info_widget_types();
        //$extra_fields = field_info_extra_fields($entity_type, $bundle, 'form');

        foreach($info as $field_name=>&$instance) {
            $instance['field_info'] = field_info_field($instance['field_name']);
        }

        return $info;
    }
}

