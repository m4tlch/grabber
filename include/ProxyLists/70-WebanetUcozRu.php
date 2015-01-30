<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.01.15
 * Time: 16:04
 */
namespace Grabber\ProxyLists;

require_once(__DIR__ . '/../Snoopy.class.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

class WebanetUcozRu {
    static function execute() {
        $proxies = array();
        $is_found = false;

        $snoopy = new \Snoopy();

        for($scan = time(); $scan > time() - 60*60*24*7; $scan -= 60*60*24) {
            $d = date('d.m.Y', $scan);
            $url = "http://webanet.ucoz.ru/freeproxy/proxylist_at_{$d}.txt";

            $snoopy->fetch($url);

            if (strstr($snoopy->response_code, '200')) {
                $is_found = true;
                break;
            }
        }

        if ($is_found) {
            // parse html
            $lines = preg_split("/\r\n|\n|\r/", $snoopy->results);

            foreach($lines as $line) {
                $exploded = explode(":", $line);

                if (count($exploded) < 2) {
                    continue;
                }

                $host = trim($exploded[0]);
                $port = trim($exploded[1]);

                // skip empty
                if (empty($host) || empty($port)) {
                    continue;
                }

                $proxies[] = "{$host}:{$port}";
            }
        }

        return $proxies;
    }
}

