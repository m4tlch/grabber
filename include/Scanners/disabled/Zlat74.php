<?php

namespace Grabber\Scanners;

require_once(__DIR__ . '/../Scanner.php');
require_once(__DIR__ . '/../Settings.php');
require_once(__DIR__ . '/../Snoopy.class.php');

use \Grabber\Scanner;

class Zlat74 extends Scanner
{
    var $user_agent = "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.94 Safari/537.36";
    var $referer = "http://zlat74.ru/";
    var $session_id = 'ft7c7a4q9smrk0laumbcqm1q54';
    var $cached = array();

    function execute() {
        $items = array();
        $snoopy = new \Snoopy();

        // set browser and referer:
        $snoopy->agent = $this->user_agent;
        $snoopy->referer = $this->referer;

        // set some cookies:
        $snoopy->cookies["PHPSESSID"] = $this->session_id;

        // set some internal variables:
        $snoopy->maxredirs = 2;

        $url = 'http://zlat74.ru/sites/default/files/prebook-schedule/schedule.js';

        if ($snoopy->fetch($url)) {
            $data = $snoopy->results;
            $data = trim($data);
            $data = substr($data, strlen('function prebookGetSchedule() { return '));
            $data = substr($data, 0, strlen($data) - strlen('; }'));
            $json = json_decode($data, TRUE);

            if (!$json) {
                throw new \Exception('Can not decode: ' . $url);
            }

            foreach($json as $row) {
                foreach($row['schedule'] as $sched) {
                    // hardcoded
                    $item = array();
                    $item['node_type'] = 'happy';
                    $item['field_city'] = 'Златоуст';
                    $item['field_place'] = 'Златоуст/КТ «Космос»';
                    $item['field_categoryhappy'] = 'Кино';
                    $item['field_simplenews_term'] = 'Златоуст';
                    $item['field_ff'] = NULL;

                    $title = $sched['name'];

                    // fix title
                    if (preg_match('/(.*)\s+(\d+\+)\s*$/', $title, $matches) ) {
                        $item['title'] = $matches[1];
                    } else {
                        $item['title'] = $title;
                    }

                    $item['title'] = trim($item['title']);

                    // 3D
                    if (strstr($item['title'], ' 3D') !== FALSE) {
                        $item['field_ff'] = '3D';
                    }

                    $item['field_tabletime_sum'] = array();

                    foreach($sched['dates'] as $date_id=>$seance ) {
                        $dt = \DateTime::createFromFormat('d.m.Y', $seance['date']);

                        foreach($seance['shows'] as $id=>$show) {
                            $time = $show['time'];

                            foreach($show['prices'] as $price) {
                                $cost = $price['price'];
                                $item['field_tabletime_sum'][] = array(
                                    'field_tabletime' => $dt->format('Y-m-d') . ' ' . $time,
                                    'field_priceticket' => $cost,
                                );

                                break; // first price
                            }
                        }
                    }

                    $this->emit($item);
                }
            }


        } else {
            throw new \Exception('Can not load: ' . $url);
        }

    }
}
