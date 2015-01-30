<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 27.11.14
 * Time: 11:20
 */

namespace Grabber;


class ProcessHelper {
    const MAX_SCANNER_THREADS = 10;

    static function execute_thread($url) {
        $headers = '';

        foreach(headers_list() as $name=>$value) {
            $headers .= "{$name}: {$value}\r\n";
        }

        $context = stream_context_create(array(
                'http'=>array(
                    'method' => "GET",
                    'timeout' => 1, // seconds
                    'header' => $headers,
                ),
            )
        );

        // open only. not read content (for speed)
        $fp = @fopen($url, 'rb', false, $context);

        if ($fp === FALSE) {
            if (isset($http_response_header[0])) {
                Logger::error("Thread not opened: {$url}: " . $http_response_header[0]); //  200 OK
            }
            return FALSE;
        }

        fclose($fp);

        return TRUE;
    }

    // run self again
    static function fork() {
        $url = $GLOBALS['base_url'] . request_uri();
        self::execute_thread($url);
    }

    static function execute_processor($class_name) {
        $url = $GLOBALS['base_url'] . "/grabber/execute/processor/{$class_name}";
        self::execute_thread($url);
    }

    static function execute_scanner($class_name) {
        for($thread_id=1; $thread_id <= self::MAX_SCANNER_THREADS; $thread_id++) {
            $semaphore = \Grabber\Registry::get_semaphore();

            if (!$semaphore->is_set("Scanner-{$class_name}-{$thread_id}")) {
                $url = $GLOBALS['base_url'] . "/grabber/execute/scanner/{$class_name}/{$thread_id}";
                self::execute_thread($url);
            }
        }
    }
}
