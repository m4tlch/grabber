<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 29.09.14
 * Time: 13:28
 */

namespace Grabber;


class TaxonomyHelper {
    var $taxonomies = array();
    var $vocabularies = array();

    function get_term($voc_name, $value, $add_missed=FALSE) {
        $value = trim($value);
        if (!isset($this->taxonomies[$voc_name])) {
            $this->load_taxonomy($voc_name);
        }

        $tid = isset($this->taxonomies[$voc_name][$value]) ? $this->taxonomies[$voc_name][$value] : FALSE;

        if ($tid === FALSE && $add_missed) {
            $vid = $this->get_vocabulary_id($voc_name);

            $term = new \stdClass();
            $term->vid = $vid;

            $exploded = explode('/', $value);
            $name = array_pop($exploded);
            $term->name = $name;
            // TODO hierarhy support

            taxonomy_term_save($term);
            $this->taxonomies[$voc_name][$value] = $term->tid;

            return $term->tid;
        }

        return $tid;
    }

    function get_vocabulary_id($name) {
        return $this->vocabularies[$name];
    }

    function load_taxonomy($voc_name) {
        $vocabulary = taxonomy_vocabulary_machine_name_load($voc_name);
        $this->vocabularies[$voc_name] = $vocabulary->vid;

        if ($terms = taxonomy_get_tree($vocabulary->vid)) {
            $indexed = array();

            foreach ($terms as $term) {
                $indexed[$term->tid] = $term;
            }

            foreach ($terms as $term) {
                $name = $this->get_branched($term, $indexed);
                $this->taxonomies[$voc_name][$name] = $term->tid;
            }
        }
    }

    function get_branched($term, &$indexed) {
        $result = $term->name;

        foreach($term->parents as $parent) {
            if ($parent) {
                $result = $this->get_branched($indexed[$parent], $indexed) . '/' . $result;
            }
        }

        return $result;
    }
} 