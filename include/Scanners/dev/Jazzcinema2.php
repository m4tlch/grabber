<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 05.10.14
 * Time: 16:45
 */

namespace Grabber;

require_once(__DIR__ . '/../include/Scanner.php');
require_once(__DIR__ . '/../include/Snoopy.class.php');

class Jazzcinema2 extends Scanner {
    function execute() {
        $item = array();

        // hardcoded
        $item['node_type'] = 'happy';
        $item['field_city'] = 'Магнитогорск';
        $item['field_place'] = 'КТ «Jazz Cinema»';
        $item['field_categoryhappy'] = 'Кино';

        //
        $snoopy = new \Snoopy;

        $snoopy = new Snoopy;
        $result = $snoopy->fetch('http://www.bravenewcode.com/custom/wordtwit-tweets.php');

        $snoopy->fetchtext("http://www.php.net/");
        print $snoopy->results;

        $snoopy->fetchlinks("http://www.phpbuilder.com/");
        print $snoopy->results;

        $submit_url = "http://lnk.ispi.net/texis/scripts/msearch/netsearch.html";

        $submit_vars["q"] = "amiga";
        $submit_vars["submit"] = "Search!";
        $submit_vars["searchhost"] = "Altavista";

        $snoopy->submit($submit_url,$submit_vars);
        print $snoopy->results;

        $snoopy->maxframes=5;
        $snoopy->fetch("http://www.ispi.net/");
        echo "<PRE>\n";
        echo htmlentities($snoopy->results[0]);
        echo htmlentities($snoopy->results[1]);
        echo htmlentities($snoopy->results[2]);
        echo "</PRE>\n";

        $snoopy->fetchform("http://www.altavista.com");
        print $snoopy->results;

        $this->emit_item_parsed($item);
    }
}
