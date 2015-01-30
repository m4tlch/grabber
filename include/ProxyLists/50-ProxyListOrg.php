<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.01.15
 * Time: 15:29
 */
namespace Grabber\ProxyLists;

require_once(__DIR__ . '/../simple_html_dom.inc.php');

class ProxyListOrg {
    static function execute() {
        $proxies = array();

        $url = 'http://proxy-list.org/ru/index.php?pp=any&pt=1&pc=any&ps=n#proxylist';

        // parse html
        $dom = file_get_html($url);

        $table = $dom->find('table table table table table table table', 0);
        $trs = $table->find('tr.RegularText');

        foreach($trs as $i=>$tr) {
            // skip header
            if ($i == 0) {
                continue;
            }

            $host =  $tr->find('td', 0)->plaintext;

            // skip empty
            if (empty($host)) {
                continue;
            }

            $proxies[] = "{$host}";
        }

        return $proxies;
    }
}

