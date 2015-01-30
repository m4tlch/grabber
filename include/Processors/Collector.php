<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 14.11.14
 * Time: 10:35
 */

namespace Grabber\Processors;

require_once (__DIR__ . "/../ProcessHelper.php");

use Grabber\ProcessHelper;

class Collector {
    function execute() {
        global $base_url;
        $scanners = $this->get_scanners();

        foreach($scanners as $scanner_name) {
            ProcessHelper::execute_scanner($scanner_name);
        }
    }

    function get_scanners() {
        $dir = __DIR__ . '/../Scanners';
        $scanners = array();

        $files = scandir($dir);

        foreach($files as $file) {
            if ($file == '..' || $file =='.') continue;
            if (!preg_match('/\.php$/', $file, $matches)) continue;

            $name = str_replace(".php", "", $file);
            $name = ucfirst($name);
            $scanners[] = $name;
        }

        return $scanners;
    }
}

