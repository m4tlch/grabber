<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 20.09.14
 * Time: 19:45
 */

namespace Grabber;

require_once('MemCache.php');
require_once('NodeHelper.php');
require_once('CommentHelper.php');
require_once('TaxonomyHelper.php');
require_once('UserHelper.php');
require_once('Logger.php');
require_once('Configurator.php');
require_once('ImageFetcher.php');
require_once(__DIR__ . '/Settings/Checkbox.php');
require_once(__DIR__ . '/Settings/Switcher.php');


class WriterFactory
{
    static function get($entity_type, $bundle_name, $field_name) {
        $cache_key = "writer-{$entity_type}-{$bundle_name}-{$field_name}";

        $writer = MemCache::get($cache_key, $default=FALSE, $persist=FALSE);

        if ($writer) {
            return $writer;
        }

        // write comments
        if ($bundle_name == 'comment') {
            $info = CommentHelper::get_info($entity_type);
        } else {
            $info = NodeHelper::get_node_info($entity_type, $bundle_name);
        }

        $field_info = $info[$field_name]['field_info'];
        $field_type = $field_info['type'];

        $map = array(
            'datetime' =>                   '\Grabber\DateFieldWriter',
            'date' =>                       '\Grabber\DateFieldWriter',
            'taxonomy_term_reference' =>    '\Grabber\TaxonomyFieldWriter',
            'field_collection' =>           '\Grabber\FieldCollectionFieldWriter',
            'list_integer' =>               '\Grabber\TextFieldWriter',
            'number_integer' =>             '\Grabber\TextFieldWriter',
            'text' =>                       '\Grabber\TextFieldWriter',
            'text_long' =>                  '\Grabber\TextFieldWriter',
            'text_with_summary' =>          '\Grabber\TextFieldWriter',
            'image' =>                      '\Grabber\ImageFieldWriter',
            'media' =>                      '\Grabber\MediaFieldWriter',
            'file'  =>                      '\Grabber\FileFieldWriter',
        );

        if (!isset($map[$field_type])) {
            throw new \Exception("No writer for: '{$field_type}'");
        }

        $class = $map[$field_type];

        // create
        $writer = new $class($field_name, $field_info);

        // configure
        Configurator::execute($writer, array('entity_type'=>$bundle_name, 'field_name'=>$field_name));

        MemCache::set($cache_key, $writer);

        return $writer;
    }
}



class FieldWriter
{
    var $field_name = '';
    var $field_info = NULL;

    function __construct($field_name, $field_info=NULL) {
        $this->field_name = $field_name;
        $this->field_info = $field_info;
    }

    function execute(&$entity, $value) {
        //
    }

    function validate($value) {
        // throw new \Exception('Validation error');
    }
}


class TaxonomyFieldWriter extends FieldWriter
{
    function execute(&$entity, $value) {
        $terms = is_array($value) ? $value : array($value);
        $voc_name = $this->get_vocabulary_name();
        $language = NodeHelper::get_entity_language($entity);
        $taxonomy_helper = new TaxonomyHelper();

        foreach($terms as $i=>$term) {
            if (empty($term)) {
                //unset ($entity->{$this->field_name}[$language][$i]);
                continue;
            }

            $tid = $taxonomy_helper->get_term($voc_name, $term, TRUE);
            $entity->{$this->field_name}[$language][$i]['tid'] = $tid;
        }
    }

    function get_vocabulary_name() {
        return $this->field_info['settings']['allowed_values'][0]['vocabulary'];
    }
}


class FieldCollectionFieldWriter extends FieldWriter
{
    var $merge = FALSE;
    var $unique = FALSE;
    var $unique_field = '';
    var $overwrite_empty_only = TRUE;

    function execute(&$entity, $value) {
        if ($entity && $this->field_name && !empty($value)) {
            $language = NodeHelper::get_entity_language($entity);

            // skip filled fields
            if (!$this->overwrite_empty_only && !empty($entity->{$this->field_name}[$language][0])) {
                return;
            }

            // merge, unique. if need
            $this->prepare($entity, $value);

            // delete old values
            $fc_items = field_get_items('node', $entity, $this->field_name);

            if ($fc_items) {
                $ids = field_collection_field_item_to_ids($fc_items);
                entity_delete_multiple('field_collection_item', $ids);

                unset($entity->{$this->field_name});
            }

            // set new values
            // multi valued field collection support
            foreach($value as $delta=>$subvalues) {
                if (empty($subvalues)) {
                    continue;
                }

                $fc = entity_create('field_collection_item', array('field_name' => $this->field_name));
                $fc->setHostEntity('node', $entity, $language);

                // set each field
                foreach($subvalues as $subfield_name=>$multivalued) {
                    $writer = WriterFactory::get('field_collection_item', $this->field_name, $subfield_name);
                    $writer->execute($fc, $multivalued);
                }

                $fc->save($skip_host_save=TRUE);

                $entity->{$this->field_name}[$language][$delta]['value'] = $fc->item_id;
                //if (!empty($entity->revision)) {
                //    $entity->{$this->field_name}[$language][$delta]['revision_id'] = $entity->revision;
                //}
            }
        }
    }

    function get_field_collection_fields() {
        $fields = field_info_instances('field_collection_item', $this->field_name);

        foreach($fields as $field_name=>&$info) {
            $info['field_info'] = field_info_field($field_name);
        }

        return $fields;
    }

    function prepare(&$entity, array &$value) {
        // 1. load
        // 2. index
        // 3. add new, update old
        // 4. generate new collection

        $entity_type = NodeHelper::get_entity_type($entity);

        // update
        if ($this->merge) {
            $merged = array();

            // load
            $fc_items = field_get_items('node', $entity, $this->field_name);

            if ($fc_items) {
                $fc_info = $this->get_field_collection_fields();

                $ids = field_collection_field_item_to_ids($fc_items);
                $fc_entities = entity_load('field_collection_item', $ids);

                foreach ($fc_entities as $fc) {
                    // skip empty
                    if ($fc === FALSE) {
                        continue;
                    }

                    $merge_row = array();

                    // each field of field collection
                    foreach($fc_info as $fc_field=>$fc_field_info) {
                        $fc_items = field_get_items('field_collection_item', $fc, $fc_field);
                        $fc_property = NodeHelper::get_central_property($fc_info, $fc_field);
                        $merge_row[$fc_field] = $fc_items[0][$fc_property];
                    }

                    $merged[] = $merge_row;
                }
            }

            // append from item
            if (isset($value)) {
                foreach($value as $v) {
                    $merged[] = $v;
                }
            }

            // store to item
            $value = $merged;
        }

        if ($this->unique) {
            $indexed = array();
            $unique_field = $this->unique_field;
            $fc_info = $this->get_field_collection_fields();

            foreach($value as $v) {
                $key = $this->get_unique_key($fc_info, $unique_field, $v[$unique_field]);
                $indexed[$key] = $v;
            }

            // sort
            ksort($indexed);

            // generate new collection
            $value = array_values($indexed);
        }
    }

    function get_unique_key($fc_info, $fc_field, $value) {
        $type = $fc_info[$fc_field]['field_info']['type'];

        switch ($type) {
            case 'date':
            case 'datetime':
                $key = (new \DateTime($value))->getTimestamp();
                break;

            default:
                $key = $value;
        }

        return $key;
    }
}


class FieldCollectionFieldWriterSettings
{
    function __construct($object) {
        $fc_info = NodeHelper::get_node_info('field_collection_item', $object->field_name);

        // options
        $values = array();
        foreach($fc_info as $fc_field=>$fc_field_info) {
            $values[$fc_field] = $fc_field_info['label'];
        }

        $this->overwrite_empty_only = (new \Grabber\Settings\Checkbox())
            ->values(array(0 => FALSE, 1 => TRUE))
            ->defaults($object->overwrite_empty_only);

        $this->merge = (new \Grabber\Settings\Checkbox())
            ->values(array(0 => FALSE, 1 => TRUE))
            ->defaults($object->merge);

        $this->unique = (new \Grabber\Settings\Checkbox())
            ->title('unique')
            ->values(array(0 => FALSE, 1 => TRUE))
            ->defaults($object->unique);

        $this->unique_field = (new \Grabber\Settings\Switcher())
            ->values($values)
            ->defaults($object->unique_field);
    }
}


class DateFieldWriter extends FieldWriter
{
    function execute(&$entity, $value) {
        $values = is_array($value) ? $value : array($value);
        $language = NodeHelper::get_entity_language($entity);

        foreach($values as $i=>$concrete_value) {
            if (empty($concrete_value)) {
                continue;
            }

            try {
                $this->validate($concrete_value);
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                continue;
            }

            $entity->{$this->field_name}[$language][$i]['value'] = $concrete_value;
        }
    }
}


class TextFieldWriter extends FieldWriter
{
    function execute(&$entity, $value) {
        $values = is_array($value) ? $value : array($value);
        $language = NodeHelper::get_entity_language($entity);

        foreach($values as $i=>$concrete_value) {
            if (empty($concrete_value)) {
                continue;
            }

            try {
                $this->validate($concrete_value);
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                continue;
            }

            $entity->{$this->field_name}[$language][$i]['value'] = $concrete_value;
        }
    }
}


class OptionsFieldWriter extends FieldWriter
{
    function execute(&$entity, $value) {
        $values = is_array($value) ? $value : array($value);
        $language = NodeHelper::get_entity_language($entity);

        foreach($values as $i=>$concrete_value) {
            if (empty($concrete_value)) {
                continue;
            }

            try {
                $this->validate($concrete_value);
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                continue;
            }

            $entity->{$this->field_name}[$language][$i]['value'] = $concrete_value;
        }
    }
}


class ImageFieldWriter extends FieldWriter
{
    var $overwrite_empty_only = TRUE; // TRUE|FALSE

    function execute(&$entity, $value) {
        global $user;

        $values = is_array($value) ? $value : array($value);
        $language = NodeHelper::get_entity_language($entity);
        $image_fetcher = Registry::get_image_fetcher();

        // skip filled fields
        if (!$this->overwrite_empty_only && !empty($entity->{$this->field_name}[$language][0])) {
            return;
        }

        // for each value
        foreach($values as $delta=>$concrete_value) {
            if (empty($concrete_value)) {
                continue;
            }

            // validate
            try {
                $this->validate($concrete_value);

            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                continue;
            }

            // fetch
            $fetched = $image_fetcher->execute($concrete_value);

            if (substr($fetched, 0, 1) == '/') {
                $fetched = 'file://' . $fetched;
            }

            // local
            $destination = 'public://pictures/'; // TODO use Drupal settings
            $file_drupal_path = file_unmanaged_copy($fetched, $destination, FILE_EXISTS_RENAME);

            // skip not downloaded
            if ($file_drupal_path === FALSE) {
                Logger::error('Can not get image: ' . $fetched);
                continue;
            }

            // skip not downloaded 2
            if (filesize($file_drupal_path) == 0) {
                unlink($file_drupal_path);
                continue;
            }

            // add image extension
            $real_path = drupal_realpath($file_drupal_path);
            $mime = $this->get_mime($real_path);

            $exploded = explode('/', $mime);
            $extension = array_pop($exploded);

            if ($extension) {
                $new_real_path = $real_path . '.' . $extension;

                $is_renamed = rename($real_path, $new_real_path);

                if ($is_renamed) {
                    $file_drupal_path = $file_drupal_path . '.' . $extension;
                }
            }

            $file = $this->add_existing_file($file_drupal_path, $user->uid);

            // fix "Undefined index: title в функции imagefield_tokens_field_attach_presave()"
            // fix "Undefined index: alt в функции imagefield_tokens_field_attach_presave()"
            $file_array = (array)$file;

            if (empty($file_array['title'])) {
                $file_array['title'] = '';
            }

            if (empty($file_array['alt'])) {
                $file_array['alt'] = '';
            }

            // write
            $entity->{$this->field_name}[$language][$delta] = $file_array;
        }
    }

    function add_existing_file($file_drupal_path, $uid, $status = FILE_STATUS_PERMANENT) {
        $files = file_load_multiple(array(), array('uri' => $file_drupal_path));
        $file = reset($files);

        if (!$file) {
            // add db record
            $file = (object) array(
                'filename' => basename($file_drupal_path),
                'filepath' => $file_drupal_path,
                'filemime' => file_get_mimetype($file_drupal_path),
                'filesize' => filesize($file_drupal_path),
                'uid' => $uid,
                'status' => $status,
                'timestamp' => time(),
                'uri' => $file_drupal_path,
            );
            drupal_write_record('file_managed', $file);
        }
        return $file;
    }

    function get_mime($file) {
        if (function_exists("finfo_file")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mime = finfo_file($finfo, $file);
            finfo_close($finfo);
            return $mime;
        } else if (function_exists("mime_content_type")) {
            return mime_content_type($file);
        } else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
            // http://stackoverflow.com/a/134930/1593459
            $file = escapeshellarg($file);
            $mime = shell_exec("file -bi " . $file);
            return $mime;
        } else {
            return false;
        }
    }
}


class ImageFieldWriterSettings
{
    function __construct($object) {
        //$writer = new ImageFieldWriter($object->field_name);

        $this->overwrite_empty_only = (new \Grabber\Settings\Checkbox())
            //->name('ImageFieldWriter?' . http_build_query(array('entity_type'=>$entity_type, 'field_name'=>$object->field_name, 'property'=>'overwrite_empty_only')))
            ->title('Перезаписывать только пустые')
            ->values(array(0 => FALSE, 1 => TRUE))
            ->defaults($object->overwrite_empty_only);
    }
}


class MediaFieldWriter extends FieldWriter
{
    function execute(&$entity, $value) {
        global $user;

        $values = is_array($value) ? $value : array($value);
        $language = NodeHelper::get_entity_language($entity);

        foreach($values as $i=>$concrete_value) {
            if (empty($concrete_value)) {
                continue;
            }

            try {
                $this->validate($concrete_value);
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                continue;
            }

            $file = $this->file_load_by_uri($concrete_value);

            // not exist -> create
            if ($file) {
                $file->display = 1;
            } else {
                $file = (object) array(
                    'uid' => $user->uid,
                    'uri' => $concrete_value,
                    'filemime' => file_get_mimetype($concrete_value),
                    'status' => 1,
                );
                $file->display = 1;
                $file->description = '';
                $file->timestamp = REQUEST_TIME;
                drupal_write_record('file_managed', $file);
                //file_save($file);
            }


            $entity->{$this->field_name}[$language][$i] = (array)$file;
        }
    }

    function file_load_by_uri($uri) {
        $uri = file_stream_wrapper_uri_normalize($uri);
        $files = entity_load('file', FALSE, array('uri' => $uri));
        return !empty($files) ? reset($files) : FALSE;
    }
}

class FileFieldWriter extends FieldWriter
{
    function execute(&$entity, $value) {
        global $user;

        $values = is_array($value) ? $value : array($value);
        $language = NodeHelper::get_entity_language($entity);

        foreach($values as $i=>$concrete_value) {
            if (empty($concrete_value)) {
                continue;
            }

            try {
                $this->validate($concrete_value);
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
                continue;
            }

            $file = $this->file_load_by_uri($concrete_value);

            // not exist -> create
            if ($file) {
                $file->display = 1;
            } else {
                $file = (object) array(
                    'uid' => $user->uid,
                    'uri' => $concrete_value,
                    'filemime' => file_get_mimetype($concrete_value),
                    'status' => 1,
                );
                $file->display = 1;
                $file->description = '';
                $file->timestamp = REQUEST_TIME;
                drupal_write_record('file_managed', $file);
                //file_save($file);
            }


            $entity->{$this->field_name}[$language][$i] = (array)$file;
        }
    }

    function file_load_by_uri($uri) {
        $uri = file_stream_wrapper_uri_normalize($uri);
        $files = entity_load('file', FALSE, array('uri' => $uri));
        return !empty($files) ? reset($files) : FALSE;
    }
}
