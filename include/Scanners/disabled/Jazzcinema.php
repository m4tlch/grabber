<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 14.11.14
 * Time: 10:43
 */

namespace Grabber\Scanners;

require_once(__DIR__ . "/../Scanner.php");
require_once(__DIR__ . "/../simple_html_dom.inc.php");

use \Grabber\Scanner;

class Jazzcinema extends Scanner {
    function execute() {
        // Create DOM from URL or file
        $url = 'http://jazzcinema.ru';
        $html = file_get_html($url);

        if (!$html) {
            throw new \Exception('Can not load: ' . $url);
        }

        // Find all items
        $days = $html->find('.content .schedule');

        foreach($days as $day) {

            // @var $event simple_html_dom_node
            foreach($day->find('li.cf') as $event) {
                // Название, жанр
                $movie = $event->find('.movie', 0);
                $item = array();

                // hardcoded
                $item['node_type'] = 'happy';
                $item['field_city'] = 'Магнитогорск';
                $item['field_place'] = 'Магнитогорск/КТ «Jazz Cinema»';
                $item['field_categoryhappy'] = 'Кино';
                $item['field_simplenews_term'] = 'Магнитогорск';
                $item['field_ff'] = NULL;

                // title
                $title = $movie->find('.title', 0)->plaintext;

                // fix title
                if (preg_match('/(.*)\((\d+\+)\)\s*$/', $title, $matches) ) {
                    $item['title'] = $matches[1];
                } else {
                    $item['title'] = $title;
                }

                $item['title'] = trim($item['title']);
                // Время, цена
                $seanses = $event->find('.seanses li');

                // 3D
                if (strstr($title, ' 3D') !== FALSE) {
                    $item['field_ff'] = '3D';
                }

                // data
                $date = $day->rel; // calendar-2014-09-15-schedule
                $date = str_replace('calendar-', '' , $date); // 2014-09-15-schedule
                $date = str_replace('-schedule', '' , $date); // 2014-09-15

                foreach($seanses as $seanse) {
                    // date + time
                    $datetime = $date . ' ' . $seanse->find('a', 0)->plaintext;

                    // digits only
                    $cost = $seanse->find('.price', 0)->plaintext;
                    $cost = filter_var($cost, FILTER_SANITIZE_NUMBER_INT);

                    $item['field_tabletime_sum'][] = array(
                        'field_tabletime' => $datetime,
                        'field_priceticket' =>  $cost,
                    );
                }

                $this->emit($item);
            }
        }

    }
}
