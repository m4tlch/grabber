<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.01.15
 * Time: 15:27
 */
namespace Grabber\ProxyLists;

require_once(__DIR__ . '/../Snoopy.class.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

class GoogleProxyNet {
    static function execute() {
        $proxies = array();

        $url = "http://www.google-proxy.net/";

        $snoopy = new \Snoopy();
        $snoopy->agent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36';
        $snoopy->fetch($url);

        // parse html
        $dom = str_get_html($snoopy->results);

        $table = $dom->find('#proxylisttable', 0);
        $trs = $table->find('tr');

        foreach($trs as $i=>$tr) {
            // skip table header
            if ($i == 0) {
                continue;
            }

            $td1 = $tr->find('td', 0);
            $td2 = $tr->find('td', 1);

            if (!$td1 || !$td2) {
                continue;
            }

            $host =  $td1->plaintext;
            $port =  $td2->plaintext;

            // skip empty
            if (empty($host) || empty($port)) {
                continue;
            }

            $proxies[] = "{$host}:{$port}";
        }

        return $proxies;
    }
}

