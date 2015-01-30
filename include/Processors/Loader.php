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
require_once(__DIR__ . '/../Configurator.php');
require_once(__DIR__ . '/../NodeHelper.php');
require_once(__DIR__ . '/../Writer.php');
require_once(__DIR__ . '/../CommentHelper.php');
require_once(__DIR__ . '/../CommentsWriter.php');
require_once(__DIR__ . '/../Logger.php');

use Grabber\CommentHelper;
use Grabber\Registry;
use Grabber\Configurator;
use Grabber\EventFactory;
use Grabber\NodeHelper;
use Grabber\NodeFilter;
use Grabber\Writer;
use Grabber\CommentsWriter;
use Grabber\Logger;

class Loader
{
    function execute() {
        $queue = Registry::get_queue();
        $events = $queue->get_images_fetched();

        foreach($events as $event) {
            // time tracking
            Registry::get_tracker()->execute();

            // FIX title
            if (empty($event['data']['title']) && !empty($event['data']['title_field'])) {
                $event['data']['title'] = $event['data']['title_field'];
            }
            if (empty($event['data']['title'])) {
                throw new \Exception("No field 'title' in data. (required)");
            }

            // strip to long
            if (strlen($event['data']['title']) > 250) {
                $event['data']['title'] = mb_substr($event['data']['title'], 0, 250);
            }

            // load
            $data = $this->load($event);
            $event_data = array_merge($event['data'], $data);
            $new_event = EventFactory::create_loaded(get_class(), $event_data);
            $queue->add($new_event);
            $queue->remove($event);
        }
    }

    function load($event) {
        $data = $event['data'];

        // lookup for exists
        $node_type = $data['node_type'];

        if (!$node_type) {
            throw new \Exception('Undefined node type.');
        }

        // filter. create and configure
        $filter = new NodeFilter($node_type);
        Configurator::execute($filter, array('entity_type'=>$node_type));

        // build filter conditions
        $conditions = $filter->execute($data);

        // find
        $entity = NodeHelper::find($node_type, $conditions);

        // not found - create
        if (!$entity) {
            $entity = NodeHelper::create($node_type, $data);
        }

        // set values
        $writer = new Writer();
        $writer->execute($entity, $data);

        // save
        NodeHelper::save($entity);

        // comments
        if (!empty($data['comments'])) {
            // foreach comment
            // find comment
            // create
            // write fields
            // save

/*
            foreach($data['comments'] as $comment_data) {
                // filter. create and configure
                $filter = new CommentFilter($node_type);
                Configurator::execute($filter, array('entity_type'=>$node_type));

                // build filter conditions
                $conditions = $filter->execute($data);

                // find
                $comment_entity = CommentHelper::find($node_type, $conditions);

                // not found - create
                if (!$comment_entity) {
                    $comment_entity = CommentHelper::create($node_type, $comment_data);
                }

                $comments_writer = new CommentsWriter();
                $comments_writer->execute($comment_entity, $comment_data);
            }
*/

            $comments_writer = new CommentsWriter();
            $comments_writer->execute($entity, $data['comments']);
        }

        return array('nid' => $entity->nid);
    }
}


