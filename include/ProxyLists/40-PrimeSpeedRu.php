<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.01.15
 * Time: 15:44
 */
namespace Grabber\ProxyLists;

require_once(__DIR__ . '/../simple_html_dom.inc.php');

class PrimeSpeedRu {
    static function execute() {
        $proxies = array();

        $url = "http://www.prime-speed.ru/proxy/free-proxy-list/all-working-proxies.php";

        // parse html
        $dom = file_get_html($url, false, null, -1, -1, true, true, DEFAULT_TARGET_CHARSET, false);

        $pre = $dom->find('div.1 pre', 0);
        $lines = preg_split("/\r\n|\n|\r/", $pre->plaintext);

        foreach($lines as $i=>$line) {
            // skip header
            if ($i < 7) {
                continue;
            }

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

        return $proxies;
    }
}

