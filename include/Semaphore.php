<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 19.10.14
 * Time: 11:34
 */

namespace Grabber;

require_once("Registry.php");

class Semaphore {
    protected $semaphores = array();
    protected $files = array();

    function __destruct() {
        $store = Registry::get_semaphores_store();

        foreach($this->semaphores as $name=>$fh) {
            $this->delete($name);
        }
    }

    function set($name) {
        $store = Registry::get_semaphores_store();
        $filename = $store->get_file_name($name);

        $dirname = dirname($filename);
        $store->make_dir($dirname);

        $fh = fopen($filename, 'w');
        $locked = flock($fh, LOCK_EX | LOCK_NB, $would_block);

        if ($locked) {
            flock($fh, LOCK_UN);
            flock($fh, LOCK_SH, $would_block);
        }

        $this->semaphores[$name] = $fh;
        $this->files[$name] = $filename;
    }

    function is_set($name) {
        $store = Registry::get_semaphores_store();
        $filename = $store->get_file_name($name);

        if (!file_exists($filename)) {
            return FALSE;
        }

        $fh = fopen($filename, 'w');

        $locked = flock($fh, LOCK_EX | LOCK_NB, $would_block);

        if ($locked) {
            flock($fh, LOCK_UN);
        }

        fclose($fh);

        return $locked ? FALSE : TRUE;
    }

    function get_request_id() {
        $id = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));
        return $id;
    }

    function delete($name) {
        $filename = $this->files[$name];
        $fh = $this->semaphores[$name];

        flock($fh, LOCK_UN);
        fclose($fh);

        unlink($filename);
    }

    function shutdown_callback () {
        unlink ( dirname ( $_SERVER [ 'SCRIPT_FILENAME' ]) . "/lock.lock" ); //otherwise it wouldn't work
        echo "<h1>Terminating</h1>\n" ;
    }
}
