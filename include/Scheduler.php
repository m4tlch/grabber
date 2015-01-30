<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 13.11.14
 * Time: 12:35
 */

namespace Grabber;

require_once ("ProcessHelper.php");
require_once ("Registry.php");

class Scheduler {
    function execute() {
        global $base_url;

        $processors = $this->get_processors();

        foreach($processors as $processor) {
            $var_name = "grabber_last_run-".get_class($processor);
            $last_run = variable_get($var_name, 0);
            $collector_period = (int)variable_get('grabber_scan_interval', 24); // hours

            // FIXME force execute when manual

            if ($processor instanceof \Grabber\Processors\Collector) {
                // Collector - 1 per day
                if (time() - $last_run > $collector_period * 60 * 60) {
                    variable_set($var_name, time());
                    ProcessHelper::execute_processor('Collector');
                }

            } else {
                // other - each time
                variable_set($var_name, time());
                $processor_name = get_class($processor);
                $processor_name = explode('\\', $processor_name);
                $processor_name = array_pop($processor_name);
                ProcessHelper::execute_processor($processor_name);
            }
        }
    }

    function get_processors() {
        $dir = __DIR__ . '/Processors';
        $matches = array();
        $processors = array();

        $files = scandir($dir);

        foreach($files as $file) {
            if ($file == '..' || $file =='.') continue;
            if (!preg_match('/\.php$/', $file, $matches)) continue;

            $name = str_replace(".php", "", $file);
            $name = ucfirst($name);

            $processors[] = Registry::get_processor($name);
        }

        return $processors;
    }
}

