<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 27.11.14
 * Time: 11:09
 */

namespace Grabber;

require_once('Registry.php');

class TrackerTimeoutException extends \Exception {};

class Tracker {
    var $max_execution_time = 0;
    var $last_run = NULL;
    var $max = 0;
    var $total = 0;
    var $reserv = 3; // seconds

    function __construct() {
        $this->max_execution_time = (int)ini_get('max_execution_time');
        $this->last_run = time();
    }

    function execute() {
        // skip first run
        if (empty($this->last_run)) {
            $this->last_run = time();
            return;
        }

        // check limit
        if ($this->is_danger()) {
            throw new TrackerTimeoutException('Timeout: ' . $this->total . '/' . $this->max_execution_time);
        }

        // update counters
        $seconds = time() - $this->last_run;

        if ($seconds > $this->max) {
            $this->max = $seconds;
        }

        $this->total += $seconds;
        $this->last_run = time();
    }

    function is_danger() {
        if ($this->total + $this->max >= ($this->max_execution_time - $this->reserv)) {
            return TRUE;
        }

        return FALSE;
    }
}
