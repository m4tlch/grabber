<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.01.15
 * Time: 16:55
 */
namespace Grabber\ProxyLists;

require_once(__DIR__ . '/../Snoopy.class.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

class RosinstrumentCom {
    static function execute() {
        $proxies = array();

        $url = "http://tools.rosinstrument.com/proxy/plab100.xml";

        $snoopy = new \Snoopy();
        $snoopy->agent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36';
        $snoopy->fetch($url);

        // parse html
        $dom = str_get_html($snoopy->results);

        $items = $dom->find('item title');

        foreach($items as $item) {
            $string = $item->plaintext;

            list($host, $port) = explode(":", $string);
            $host = trim($host);
            $port = trim($port);

            // skip empty
            if (empty($host) || empty($port)) {
                continue;
            }

            $proxies[] = "{$host}:{$port}";
        }

        return $proxies;
    }
}
