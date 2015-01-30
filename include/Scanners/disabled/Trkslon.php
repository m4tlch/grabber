<?php

namespace Grabber\Scanners;

require_once(__DIR__ . '/../Scanner.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

use \Grabber\Scanner;

class Trkslon extends Scanner
{
    var $user_agent = "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.94 Safari/537.36";
    var $referer = "http://trkslon.ru/kino/films/";
    var $session_id = 'ft7c7a4q9smrk0laumbcqm1q54';
    var $cached = array();

    function execute() {
        $headers = "";

        $url = 'http://trkslon.ru/kino/films/';

        $opts = array(
            'http'=>array(
                'method' => "GET",
                'header' => $headers
            )
        );

        $context = stream_context_create($opts);

        $html = file_get_html($url, false, $context);

        if ($html) {
            // 1. get all film links
            // 2. parse each
            // 3. return schedule

            // 1. get all film links
            $movies = $html->find('.movie_item h3 a');

            foreach($movies as $movie) {
                // 2. parse each
                //$movie->title;
                $url = 'http://trkslon.ru' . trim($movie->href);
                $item = $this->parse_movie($url);

                // 3. return schedule
                $this->emit($item);
            }

        } else {
            throw new \Exception('Can not load: ' . $url);
        }

    }

    function parse_movie($url) {
        $headers = "";

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
            $item['field_city'] = 'Миасс';
            $item['field_place'] = 'Миасс/КТ «Гавайи»';
            $item['field_categoryhappy'] = 'Кино';
            $item['field_simplenews_term'] = 'Миасс';
            $item['field_ff'] = NULL;

            // title
            $title = $html->find('.content h1', 0)->plaintext;
            $title = trim($title);
            $item['title'] = $title;

            if (strstr($title, ' 3D') !== FALSE) {
                $item['field_ff'] = '3D';
            }

            // schedule
            $item['field_tabletime_sum'] = array();

            $sessions = $html->find('.sessions_list');

            // each day
            foreach($sessions as $session) {
                $date_string = $session->find('h4', 0)->plaintext; // Сеансы на сегодня, 4 ноября
                $date = $this->parse_russian_date($date_string);

                $times = $session->find('.sessions_ul li');

                // each time
                foreach($times as $time_li) {
                    // skip old seances
                    if (!$time_li->find('a', 0)) {
                        continue;
                    }

                    $time = $time_li->find('a', 0)->plaintext;
                    $cost = $time_li->find('.price', 0)->plaintext;

                    $cost = filter_var($cost, FILTER_SANITIZE_NUMBER_INT);
                    $cost = intval($cost);

                    $item['field_tabletime_sum'][] = array(
                        'field_tabletime' => $date . ' ' . $time,
                        'field_priceticket' => $cost,
                    );
                }
            }

            return $item;

        } else {
            throw new \Exception('Can not load: ' . $url);
        }
    }

    function parse_russian_date($date_string) {
        $ru_month = array( 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь' );
        $ru_month2 = array( 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );
        $en_month = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

        $date_string = htmlentities($date_string);
        $date_string = str_replace('&nbsp;', ' ', $date_string);
        $date_string = html_entity_decode($date_string);
        $date_string = strtolower($date_string);
        $englished = str_replace($ru_month, $en_month, $date_string);
        $englished = str_replace($ru_month2, $en_month, $englished);
        $englished = str_replace('Сеансы на сегодня,', '', $englished);
        $englished = str_replace('Сеансы на завтра,', '', $englished);
        $englished = str_replace('Сеансы на', '', $englished);
        $englished = trim($englished);

        $da = date_parse_from_format("j F Y", $englished);

        $current_year = intval(date('Y'));
        $current_month = intval(date('m'));

        $year = ($current_month == 12 && $da['month'] == 1) ? $current_year + 1 : $current_year;
        $day = str_pad($da['day'], 2, '0', STR_PAD_LEFT);
        $month = str_pad($da['month'], 2, '0', STR_PAD_LEFT);

        $date = $year . '-' . $month . '-' . $day;

        return $date;
    }
}
