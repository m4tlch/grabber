<?php

namespace Grabber\Scanners;

require_once(__DIR__ . '/../Scanner.php');
require_once(__DIR__ . '/../Snoopy.class.php');

use \Grabber\Scanner;

class Skycinema extends Scanner
{
    var $user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
    var $referer = "http://sky-cinema.ru/";
    var $session_id = '6br726hpc86l2gf0jb83m6rk53';
    var $cached = array();

    function execute() {
        $timestamps = $this->get_table('table_');

        foreach($timestamps as $timestamp) {
            $this->get_schedule($timestamp, 'cinema_table');
        }
    }

    function get_table($table) {
        $result = array();
        $snoopy = new \Snoopy();

        // set browser and referer:
        $snoopy->agent = $this->user_agent;
        $snoopy->referer = $this->referer;

        // set some cookies:
        $snoopy->cookies["PHPSESSID"] = $this->session_id;

        // set some internal variables:
        $snoopy->maxredirs = 2;

        $url = 'http://sky-cinema.ru/cinema/service/get_days/' . $table;

        if ($snoopy->fetchtext($url)) {
            $decoded_all = json_decode($snoopy->results);

            if (!$decoded_all) {
                throw new \Exception('Can not decode: ' . $url);
            }

            foreach($decoded_all as $decoded) {
                $result[] = $decoded->timestamp;
            }
        } else {
            throw new \Exception('Can not load: ' . $url . '. ' . $snoopy->error);
        };

        return $result;
    }

    function get_schedule($timestamp, $table) {
        $snoopy = new \Snoopy();

        // set browser and referer:
        $snoopy->agent = $this->user_agent;
        $snoopy->referer = $this->referer;

        // set some cookies:
        $snoopy->cookies["PHPSESSID"] = $this->session_id;

        // set some internal variables:
        $snoopy->maxredirs = 2;

        $post = array(
            'get' => 'schedule',
            'table' => $table,
            'timestamp' => $timestamp,
        );

        $url = "http://sky-cinema.ru/cinema/service";

        if ($snoopy->submit($url, $post)) {
            $decoded_all = json_decode($snoopy->results);

            foreach($decoded_all as $decoded) {
                foreach($decoded->seances as $seance) {
                    // hardcoded
                    $item = array();
                    $item['node_type'] = 'happy';
                    $item['field_city'] = 'Магнитогорск';
                    $item['field_place'] = 'Магнитогорск/КТ «Sky Cinema Гостиный двор»';
                    $item['field_categoryhappy'] = 'Кино';
                    $item['field_simplenews_term'] = 'Магнитогорск';
                    $item['field_ff'] = NULL;

                    $item['title'] = trim($decoded->title);
                    //$item['field_poster'] = 'http://sky-cinema.ru/content/media/thumbs/cinema_'.$decoded->id.'/' . $decoded->poster;
                    $item['field_tabletime_sum'] = array();

                    if ($seance->is3d == "1") {
                        $item['field_ff'] = '3D';
                    }

                    list($hour, $min) = explode(':', $seance->time);

                    $date = new \DateTime();
                    $date->setTimestamp($timestamp);
                    $date->setTime($hour, $min);

                    $item['field_tabletime_sum'] = array(
                        'field_tabletime' => $date->format('Y-m-d H:i:s'),
                        'field_priceticket' => $seance->price,
                    );

                    $this->emit($item);
                }
            }

        }
        else {
            throw new \Exception('Can not load: ' . $url . '. ' . $snoopy->error);
        }
    }

    function get_film($id) {
        if (isset($this->cached[$id])) {
            return $this->cached[$id];
        }

        $item = array();
        $snoopy = new \Snoopy();

        // set browser and referer:
        $snoopy->agent = $this->user_agent;
        $snoopy->referer = $this->referer;

        // set some cookies:
        $snoopy->cookies["PHPSESSID"] = $this->session_id;

        // set some internal variables:
        $snoopy->maxredirs = 2;

        $post = array(
            'get' => 'film',
            'id' => $id,
        );

        if ($snoopy->submit("http://sky-cinema.ru/cinema/service", $post)) {
            $decoded = json_decode($snoopy->results);

            $item['title'] = trim($decoded->title);
            $item['field_media'] = $decoded->trailer;
            $item['field_ff'] = $decoded->is3d ? '3D' : '';
            $item['field_year'] = $decoded->year;
            $item['field_time'] = $decoded->duration;
            $item['field_regiser'] = $decoded->director;
            $item['field_country'] = $decoded->country;
            $ganr = explode(',', $decoded->genre);
            $ganr = array_map('trim', $ganr);
            $ganr = array_map('strtolower', $ganr);
            $item['field_ganr'] = $ganr;
            $item['field_roles'] = implode(',', $decoded->starring);
            $item['field_context'] = $decoded->description;
        }

        $this->cached[$id] = $item;

        return $item;
    }
}
