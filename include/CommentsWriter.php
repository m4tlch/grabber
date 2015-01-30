<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 20.09.14
 * Time: 19:42
 */

namespace Grabber;

require_once('WriterFactory.php');
require_once('NodeHelper.php');


class CommentsWriter
{
    function execute(&$entity, $comments) {
        foreach($comments as $comment) {
            $comment_entity = CommentHelper::find($entity->nid, $comment['comment_body']);

            if (empty($comment_entity)) {
                $comment_entity = CommentHelper::create($entity, $comment);
            }

            $entity_type = NodeHelper::get_entity_type($entity);
            $this->write_fields($comment_entity, $comment, $entity_type);

            CommentHelper::save($comment_entity);
        }
    }

    function write_fields(&$comment_entity, $comment, $entity_type) {
        $reserved = array('nid', 'cid', 'pid', 'uid', 'mail', 'is_anonymous', 'homepage', 'status');

        // get comment fields
        $info = CommentHelper::get_info($entity_type);

        foreach($comment as $field_name=>$value) {
            // skip non entity fields
            if (empty($info[$field_name]) || in_array($field_name, $reserved)) {
                continue;
            }

            // write
            $field_writer = WriterFactory::get($entity_type, 'comment',   $field_name);
            $field_writer->execute($comment_entity, $value);
        }
    }
}
