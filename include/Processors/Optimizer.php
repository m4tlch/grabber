<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 13.11.14
 * Time: 13:34
 */

namespace Grabber\Processors;

require_once(__DIR__ . '/../Registry.php');
require_once(__DIR__ . '/../Settings.php');
require_once(__DIR__ . '/../Event.php');
require_once(__DIR__ . '/../ProcessHelper.php');

use \Grabber\Registry;
use \Grabber\EventFactory;
use \Grabber\Settings;
use \Grabber\ProcessHelper;


class Optimizer
{
    /* @var $queue \Grabber\Queue */
    var $queue = NULL;

    function execute() {
        $this->queue = Registry::get_queue();

        $queue = Registry::get_queue();
        $events = $queue->get_scanned();
        $this->optimize($events);

        ProcessHelper::execute_processor("Filler");
    }

    function optimize(array &$items) {
        if (empty($items)) {
            return;
        }

        // group by node type
        $by_type = $this->group_by($items, array('node_type'));

        // optimize for each node type
        foreach($by_type as $node_type=>$node_type_items) {
            $group_fields = Settings::get('Grabber\\NodeFilter', array('entity_type'=>$node_type), 'fields');
            $group_fields = is_string($group_fields) ? json_decode($group_fields) : $group_fields;

            // group
            $grouped = $this->group_by($node_type_items, $group_fields);

            // compress values
            $compressed = $this->compress($grouped, $group_fields);
/*
            foreach($compressed as $c) {
                var_dump($c['data']['comments']);
            }
            die();
*/
            // create new events
            $queue = Registry::get_queue();

            foreach($compressed as $event) {
                $new_event = EventFactory::create_optimized(get_class(), $event['data']);
                $queue->add($new_event);
            }

            // remove source events
            foreach($node_type_items as $event) {
                $queue->remove($event);
            }
        }
    }

    function merge(&$item1, $item2) {
        foreach($item2['data'] as $key2=>$value2) {
            // skip empty value
            if (empty($value2) && !empty($item1['data'][$key2])) {
                continue;
            }

            // merge comments
            if ($key2 == 'comments') {
                // remove duplicates
                foreach ($value2 as $comment2) {
                    $is_dup_exists = false;

                    if (!empty($item1['data']['comments'])) {
                        // search dup
                        foreach($item1['data']['comments'] as $comment1) {
                            // check dup
                            if ($comment1['comment_body'] == $comment2['comment_body']
                                && !empty($comment1['author']['name'])
                                && !empty($comment2['author']['name'])
                                && ($comment1['author']['name'] == $comment2['author']['name'])
                            )
                            {
                                // skip dup
                                $is_dup_exists = true;
                                break;
                            }
                        }
                    }

                    if ($is_dup_exists) {
                        continue;
                    }

                    // add new comment
                    $item1['data']['comments'][] = $comment2;
                }
                continue;
            }

            // merge reserved 'author' field. aways use last
            if ($key2 == 'author') {
                if (empty($item1['data']['author'])) {
                    $item1['data']['author'] = $item2['data']['author'];
                    continue;
                }

                $old_author = $item1['data']['author'];

                if (is_array($item2['data']['author'])) {
                    $item1['data']['author'] = $item2['data']['author'];

                    if (empty($item1['data']['author'])) {
                        $item1['data']['author'] = is_array($old_author) ? $old_author['name'] : $old_author;
                    }

                } else {
                    if (is_array($item1['data']['author'])) {
                        $item1['data']['author']['name'] = is_array($old_author) ? $old_author['name'] : $old_author;
                    }
                }
                continue;
            }

            // merge taxonomy array
            if (is_array($value2)) {
                $k = key($value2);
                $v = current($value2);

                // really taxonomy
                if (is_numeric($k) && is_string($v)) {
                    // merge
                    $value1 = $item1['data'][$key2];
                    $merged = array_merge($value1, $value2);
                    $uniqued = array_unique($merged);
                    $item1['data'][$key2] = $uniqued;
                    continue;
                }

            }

            // merge field collection
            if (is_array($value2)) {
                reset($value2);
                $k = key($value2);
                $v = current($value2);

                // is really field collection.
                if (is_string($k) || (is_array($v)))  {
                    // single value 2 - to multi-value
                    if (is_string($k)) {
                        $fc_item2 = array($value2);
                    } else {
                        $fc_item2 = $value2;
                    }

                    // single value 1 - to multi-value
                    $fc_item1 = array();

                    if (isset($item1['data'][$key2])) {
                        $value1 = $item1['data'][$key2];

                        reset($value1);
                        $k1 = key($value1);

                        if (is_array($value1) && is_string($k1)) {
                            $fc_item1 = array($value1);
                        } else {
                            $fc_item1 = $value1;
                        }
                    }

                    // merge
                    $merged = array_merge($fc_item1, $fc_item2);
                    $item1['data'][$key2] = $merged;
                    continue;
                }
            }

            // overwrite simple field
            $item1['data'][$key2] = $value2;
        }
    }

    function group_by(array $rows, array $group_fields) {
        $grouped = array();

        // group by fields
        foreach($rows as &$row) {
            $current =& $grouped;
            $groups = $group_fields;

            // group
            while ($group_field = array_shift($groups)) {
                $value = !empty($row['data'][$group_field]) ? $row['data'][$group_field] : NULL;

                // branch
                if (empty($current[$value])) {
                    $current[$value] = array();
                }

                $current =& $current[$value];
            }

            // append (leaf)
            $current[] = $row;
        }

        return $grouped;
    }

    function compress($values, $group_fields) {
        $compressed = array();

        while (!empty($group_fields)) {
            array_shift($group_fields);

            // groups
            if (!empty($group_fields)) {
                $vals = array();

                foreach($values as $value) {
                    $add = array_values($value);
                    $vals = array_merge($vals, $add);
                }

                $values = $vals;

            } else {
                // leaf values
                foreach($values as $value) {
                    $add = array_values($value);
                    $item1 = array_shift($add);

                    foreach($add as $item2) {
                        $this->merge($item1, $item2);
                    }

                    $compressed[] = $item1;
                }
            }
        }

        return $compressed;
    }

}
