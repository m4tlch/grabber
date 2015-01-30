<?php

namespace Grabber\Scanners;

require_once(__DIR__ . '/../Scanner.php');
require_once(__DIR__ . '/../Snoopy.class.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

use \Grabber\Scanner;

class Sovremennik extends Scanner
{
    var $user_agent = "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.94 Safari/537.36";
    var $referer = "http://sovremennik-kino.ru/cinema/index/";
    var $session_id = 'ft7c7a4q9smrk0laumbcqm1q54';
    var $cached = array();

    function execute() {
        $date = new \DateTime();
        $date->modify('today');
        $this->get_day($date);

        $date->modify('+1 day');
        $this->get_day($date);

        $date->modify('+1 day');
        $this->get_day($date);

        $date->modify('+1 day');
        $this->get_day($date);
    }

    function get_day(\DateTime $date) {
        // http://sovremennik-kino.ru/cinema/getfilmtable/daycode_2014-10-19
        $headers = "";

        $url = 'http://sovremennik-kino.ru/cinema/getfilmtable/daycode_' . $date->format('Y-m-d');

        $opts = array(
            'http'=>array(
                'method' => "POST",
                'header' => $headers
            )
        );

        $context = stream_context_create($opts);

        $html = file_get_html($url, false, $context);

        if ($html) {
            // Find all items
            $trs = $html->find('#cont_wrap tr');

            foreach($trs as $tr) {
                // hardcoded
                $item = array();
                $item['node_type'] = 'happy';
                $item['field_city'] = 'Магнитогорск';
                $item['field_place'] = 'Магнитогорск/КТ «Современник»';
                $item['field_categoryhappy'] = 'Кино';
                $item['field_simplenews_term'] = 'Магнитогорск';
                $item['field_ff'] = NULL;

                $title = $tr->first_child()->plaintext;

                $item['title'] = trim($title);

                if (strstr($item['title'], ' 3D') !== FALSE) {
                    $item['field_ff'] = '3D';
                }

                $time = $tr->first_child()->next_sibling()->plaintext;
                $cost = $tr->first_child()->next_sibling()->next_sibling()->plaintext;
                $cost = filter_var($cost, FILTER_SANITIZE_NUMBER_INT);

                $item['field_tabletime_sum'] = array(
                    'field_tabletime' => ($date->format('Y-m-d ')) . $time,
                    'field_priceticket' => $cost,
                );

                $this->emit($item);
            }

        } else {
            throw new \Exception('Can not load: ' . $url);
        }

    }
}
