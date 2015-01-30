<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.01.15
 * Time: 16:34
 */
namespace Grabber\ProxyLists;

require_once(__DIR__ . '/../Snoopy.class.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

class SpysRu {
    static function execute() {
        $proxies = array();
        return $proxies;

        $url = "http://spys.ru/en/http-proxy-list/";

        // parse html
        $dom = file_get_html($url);

        $trs = $dom->find('table table tr');

        foreach($trs as $tr) {
            $cell = $tr->find('td font.spy14', 0);
;
            if (!$cell) {
                continue;
            }

            $exploded = explode(":", $cell->plaintext);
            var_dump($cell->children[0]->outertext);

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
