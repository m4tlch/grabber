<?php

namespace Grabber;

require_once(__DIR__ . '/../include/Scanner.php');
//require_once(__DIR__ . '/../include/Snoopy.class.php');
require_once(__DIR__ . '/../include/simple_html_dom.inc.php');


/**
 * Class Volna
 * @package Grabber
 */
class Volna extends Scanner
{
    var $user_agent = "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.94 Safari/537.36";
    var $referer = "http://www.chebar-cool.ru/volna/";
    var $session_id = 'ft7c7a4q9smrk0laumbcqm1q54';
    var $cached = array();

    function execute() {
        $items = array();
        $headers = "";

        $url = 'http://www.chebar-cool.ru/volna/page_2.html';

        $opts = array(
            'http'=>array(
                'method' => "GET",
                'header' => $headers
            )
        );

        $context = stream_context_create($opts);

        $html = file_get_html($url, false, $context);

        if ($html) {
            $item = array();

            // hardcoded
            $item['node_type'] = 'happy';
            $item['field_city'] = 'Чебаркуль';
            $item['field_place'] = 'Дщппук';
            $item['field_categoryhappy'] = 'Кино';

            $rows = $html->find('.news-item table tr');

            foreach($rows as $row) {
                $title = $row->find('td', 1)->plaintext;
                $item['title'] = trim($title);

                $date = (new \DateTime())->format('Y-m-d');

                $movie_times = $row->find('td', 2)->plaintext;
                $item['field_tabletime_sum'] = array();

                foreach($movie_times as $movie_time) {
                    $time = $movie_time->plaintext;

                    $item['field_tabletime_sum'][] = array(
                        'field_tabletime' => $date . ' ' . $time,
                        'field_priceticket' => '',
                    );
                }

                var_dump($item);
                $this->emit_item_parsed($item);
            }


        } else {
            throw new \Exception('Can not load: ' . $url);
        }

    }
}

$t = new Trkslon();
$t->execute();
