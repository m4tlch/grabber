<?php

namespace Grabber;

require_once('WriterFactory.php');


class GrabberEntityFieldQuery extends \EntityFieldQuery
{
    protected $absent = array();

    function finishQuery($select_query, $id_key = 'entity_id')
    {
        if ($this->age == FIELD_LOAD_CURRENT) {
            $tablename_function = '_field_sql_storage_tablename';
            $id_key = 'entity_id';
        }
        else {
            $tablename_function = '_field_sql_storage_revision_tablename';
            $id_key = 'revision_id';
        }

        $table_aliases = array();

        reset($this->fields);
        $key = key($this->fields);
        $field = current($this->fields);
        $tablename = $tablename_function($field);
        $field_base_table = $tablename . $key;

        $key = count($this->fields) + 1;

        foreach($this->absent as $field) {
            $tablename = $tablename_function($field);
            // Every field needs a new table.
            $table_alias = $tablename . $key;
            $table_aliases[$key] = $table_alias;

            $select_query->leftJoin(
                $tablename,
                $table_alias,
                "$table_alias.entity_type = $field_base_table.entity_type AND $table_alias.$id_key = $field_base_table.$id_key"
            );

            $select_query->where("$table_alias.$id_key IS NULL");

            $key++;
        }

        return parent::finishQuery($select_query, $id_key);
    }

    public function absentFieldCondition($field) {
        $field_definition = field_info_field($field);
        $this->absent[] = $field_definition;
    }
}


class NodeHelper
{
    static function find($node_type='', array $conditions=array()) {
        $info = self::get_node_info('node', $node_type);

        $query = new GrabberEntityFieldQuery();
        $query
            ->entityCondition('entity_type', 'node')
            ->deleted(FALSE);

        if ($node_type)
            $query->propertyCondition('type', $node_type);

        foreach($conditions as $field=>$value) {
            if (($field == 'title' || $field == 'status')) {
                if (!empty($value)) {
                    $query->propertyCondition($field, $value);
                }
            } else {
                // absent field
                if (is_null($value)) {
                    $query->absentFieldCondition($field);
                } else {
                    $property = self::get_central_property($info, $field);
                    $query->fieldCondition($field, $property, $value);
                }
            }
        }

        $entities = $query
            ->range(0,1)
            ->execute();

        if (!empty($entities['node'])) {
            $one = array_shift($entities['node']);
            $node = node_load($one->nid);
            return $node;
        }

        return FALSE;
    }

    /**
     * @param string $node_type
     * @param array $data
     * @return bool
     */
    static function create($node_type, array $data=array()) {
        // author
        if (!empty($data['author'])) {
            $uid = UserHelper::get_author($data['author']);

        } else {
            $uid = (int)variable_get('grabber_author', '0');
        }

        // node
        $entity = entity_create('node', array(
                'type' => $node_type,
                'uid' => $uid,
                'title' => $data['title'],
            ));

        node_object_prepare($entity);

        $entity->uid = $uid;

        return $entity;
    }

    static function get_central_property($info, $field_name) {
        $field_info = $info[$field_name]['field_info'];
        $field_type = $field_info['type'];
        $settings = $info[$field_name];

        $map = array(
            'datetime' =>                'value',
            'date' =>                    'value',
            'datestamp' =>               'value',
            'taxonomy_term_reference' => 'tid',
            'field_collection' =>        'value',
            'text' =>                    'value',
            'text_long' =>               'value',
            'text_with_summary' =>       'value',
            'number_integer' =>          'value',
            'image' =>                   'fid',
            'media' =>                   'fid',
            'file' =>                   ' fid',
        );

        if (!isset($map[$field_type])) {
            throw new \Exception("Unsupported field type: {$field_type}");
        }

        return $map[$field_type];
    }

    static function get_node_info($entity_type, $bundle_name) {
        $info = field_info_instances($entity_type, $bundle_name);

        //$field_types = field_info_field_types();
        //$widget_types = field_info_widget_types();
        //$extra_fields = field_info_extra_fields($entity_type, $bundle, 'form');

        foreach($info as $field_name=>&$instance) {
            $instance['field_info'] = field_info_field($instance['field_name']);
        }

        return $info;
    }

    static function get_node_fields($node_type) {
        $info = self::get_node_info('node', $node_type);
        return array_keys($info);
    }

    static function get_image_fields($node_type) {
        $result = array();
        $info = self::get_node_info('node', $node_type);

        foreach($info as $field=>$field_info) {
            $field_type = $field_info['field_info']['type'];
            if ($field_type == 'image') {
                $result[] = $field;
            }
        }

        return $result;
    }

    static function get_reserved_fields() {
        return array('title', 'status', 'sticky', 'promote', 'created', 'changed', 'timestamp', 'nid', 'uid', 'revision', 'comments');
    }

    static function get_entity_type($entity) {
        return method_exists($entity, 'entityType') ? $entity->entityType() : $entity->type;
    }

    static function get_entity_language($entity) {
        return LANGUAGE_NONE;
        return method_exists($entity, 'langcode') ? $entity->langcode() : $entity->language;
    }

    static function save($entity) {
        $uid = (int)variable_get('grabber_author', '0');

        if (empty($entity->uid)) {
            $entity->uid = $uid;
        }

        return method_exists($entity, 'save') ? $entity->save() : node_save($entity);
    }

    static function set_deefault_values($entity, $field) {
        $items = field_get_default_value(self::get_entity_type($entity), $entity, $field, $instance);
        return $items;
    }

    static function get_field_vocabulary($field_info) {
        $voc_name = $field_info['settings']['allowed_values'][0]['vocabulary'];
        return $voc_name;
    }
}


class NodeFilter
{
    var $fields = array();
    var $entity_type = '';

    function __construct($entity_type) {
        $this->entity_type = $entity_type;
    }

    function execute($item) {
        $prepared = array();

        if (empty($this->fields)) {
            throw new \Exception('Loader not configured (no fields): for type: ' . $this->entity_type . '. Configuration here: admin/config/administration/grabber/loader/settings/' . $this->entity_type);
        }

        $this->fields = is_string($this->fields) ? json_decode($this->fields) : $this->fields;

        $reserved = array('title', 'status');
        $info = NodeHelper::get_node_info('node', $this->entity_type);

        $taxonomy_helper = new TaxonomyHelper();

        foreach($this->fields as $field) {
            if (in_array($field, $reserved)) {
                $prepared[$field] = $item[$field];
                continue;
            }

            $field_info = $info[$field]['field_info'];
            $field_type = $field_info['type'];

            if ($field_type == 'taxonomy_term_reference') {
                if (empty($item[$field])) {
                    $prepared[$field] = NULL;
                    continue;
                }

                $voc_name = NodeHelper::get_field_vocabulary($field_info);
                $prepared[$field] = $taxonomy_helper->get_term($voc_name, $item[$field], $add_missed=FALSE);
            } else {
                $prepared[$field] = $item[$field];
            }
        }

        return $prepared;
    }
}

class NodeFilterSettings
{
    function __construct($object) {
        // get node fields
        $info = \Grabber\NodeHelper::get_node_info('node', $object->entity_type);

        // special
        $values = array(
            'title' => t('Title'),
            'status' => t('Status'),
        );

        foreach ($info as $field_name=>$big_info) {
            $values[$field_name] =  $big_info['label'];
        }

        asort($values);

        $fields = is_string($object->fields) ? json_decode($object->fields) : $object->fields;

        $this->fields = (new \Grabber\Settings\Chain())
            ->title(t('Find node by (fields):'))
            ->values($values)
            ->defaults($fields);
    }
}

