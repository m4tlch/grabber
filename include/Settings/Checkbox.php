<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 25.10.14
 * Time: 15:49
 */

namespace Grabber\Settings;


class Checkbox {
    var $title = '';
    var $values = array();
    var $defaults = NULL;
    var $url = '';

    function title($title) {
        $this->title = $title;
        return $this;
    }

    function values($values) {
        $this->values = $values;
        return $this;
    }

    function defaults($value) {
        $this->defaults = $value;
        return $this;
    }

    function url($url) {
        $this->url = $url;
        return $this;
    }

    function render() {
        $module_path = drupal_get_path('module', 'grabber');
        drupal_add_js($module_path . '/js/widgets.js', 'file');
        drupal_add_css($module_path . '/css/widgets.css', 'file');

        $values = json_encode($this->values);
        $defaults = $this->defaults ? '1' : '0';
        $url = urlencode($this->url);

        ob_start();
        ?>

        <div class="checkbox" data-defaults='<?= $defaults ?>' data-values='<?= $values ?>' data-url='<?= $url ?>'></div>

        <?php
        $html = ob_get_contents();
        $html = trim($html);
        ob_end_clean();

        return $html;
    }
}


