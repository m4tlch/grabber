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


class Writer
{
    function execute(&$entity, $data) {
        $reserved = NodeHelper::get_reserved_fields();

        // get node fields
        $info = NodeHelper::get_node_info('node', NodeHelper::get_entity_type($entity));

        foreach($data as $field_name=>$value) {
            // skip non entity fields
            if (empty($info[$field_name]) || in_array($field_name, $reserved)) {
                continue;
            }

            // write
            $field_writer = WriterFactory::get('node',  NodeHelper::get_entity_type($entity), $field_name);
            $field_writer->execute($entity, $value);
        }
    }
}
