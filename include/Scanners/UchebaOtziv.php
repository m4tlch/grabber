<?php

namespace Grabber\Scanners;

require_once(__DIR__ . '/../Registry.php');
require_once(__DIR__ . '/../Scanner.php');
require_once(__DIR__ . '/../Logger.php');
require_once(__DIR__ . '/../Downloader.php');
require_once(__DIR__ . '/../Store.php');
require_once(__DIR__ . '/../Event.php');

use Grabber\Logger;
use \Grabber\Registry;
use \Grabber\Downloader;
use \Grabber\Event;
use \Grabber\EventFactory;
use \Grabber\Scanner;


class UchebaOtziv extends Scanner
{
    //const MAX_THREADS = 10;
    const PARSER_INDEX = 'LinksQueue.parse_index';
    const PARSER_ZAVED = 'LinksQueue.parse_zaved';
    const PARSER_COMMENT = 'LinksQueue.parse_comment';

    function execute() {
        $url_and_data = $this->get_url();

        if (!$url_and_data) {
            //$this->prepare_urls();
            //$url_and_data = $this->get_url();
        }

        for(; $url_and_data; $url_and_data = $this->get_url()) {
            // time limit detection
            Registry::get_tracker()->execute();

            $url = $url_and_data['url'];
            $data = $url_and_data['data'];

            try {
                $dom = Downloader::get($url, Downloader::TYPE_HTML_DOM);
                $this->parse($dom, $data, $url);
                $this->remove_url($url);

            } catch (\Exception $e) {
                Logger::log($e->getMessage());
            }

        }
    }

    function parse($dom, $data, $url) {
        $parser = $data['parser'];

        switch($parser) {
            case self::PARSER_ZAVED:
                $this->parse_zaved($dom, $data, $url);
                break;

            case self::PARSER_COMMENT:
                $this->parse_comment($dom, $data);
                break;

            case self::PARSER_INDEX:
                $this->parse_index($dom, $data);
                break;

            default:
                throw new \Exception("Unsupported parser: '{$parser}'");
        }
    }

    function parse_index($dom, $data) {
        $content = $dom->find('#content', 0);

        $first_tr = $content->find('table tr', 0);
        $tr = $first_tr;

        while ($tr) {
            $tr1 = $tr;
            $tr2 = $tr1->next_sibling();

            $headings = array();
            $headings_a = $tr2->find('td', 0)->find('a');

            foreach($headings_a as $a) {
                $headings[] = $a->plaintext;
            }

            // save url to queue. for later parsing
            $this->add_url("http://ucheba-otziv.ru" . trim($tr1->find('td', 1)->find('a', 0)->href), array(
                'parser' => self::PARSER_ZAVED,
                'field_heading' => $headings,
            ));

            $tr = $tr2->next_sibling();
        }
    }

    function parse_zaved($dom, $data, $url) {
        // hardcoded
        $item = array();
        $item['node_type'] = 'zaved';

        // zaved
        $content = $dom->find('#content', 0);

        if (!$content) {
            throw new \Exception("No #content.");
        }

        $zaved = $content->find('table#comment_full', 0);
        $td1 = $zaved->find('td.full_userinfo_td', 0);
        if ($td1) {
            $item['field_photo'] = "http://ucheba-otziv.ru" . $td1->find('img', 0)->src;
        } else {
            $item['field_photo'] = '';
        }

        $td2 = $zaved->find('td.full_title_td', 0);
        $item['title'] = $td2->find('h1', 0)->plaintext;
        $item['title_field'] = $item['title'];
        $item['field_contact'] = $td2->find('span.fsize12px', 0)->plaintext;
        $item['field_contact'] = html_entity_decode($item['field_contact']);
        $item['field_description'] = trim($td2->find('div.full_commtxt', 0)->plaintext);
        $item['field_description'] = html_entity_decode($item['field_description']);
        $item['field_heading'] = $data['field_heading'];
        $item['author'] = array(
            'name' => $td2->find('div.full_commtxt', 0)->next_sibling()->find('b', 0)->plaintext,
        );

        // comments
        $item['comments'] =  $this->parse_comment_on_page($dom);
        $this->emit($item);

        // get last comment page
        $com_pages = $content->find('span a');

        if ($com_pages) {
            $last_page = end($com_pages);
            $last_page_href = $last_page->href;
            //$query = parse_url($last_page_href, 'query');
            $query = substr($last_page_href, 1);
            parse_str($query, $params);
            $curpos = (int)$params['curPos'];

            // 3 * 18 = 54 comments. skip current
            for($page = 0; $page < 4 && $curpos && $curpos > 0; $curpos -= 18, $page++) {
                $this->add_url($url . "?curPos=" . $curpos, array(
                    'parser' => self::PARSER_COMMENT,
                    'title' => $item['title_field'], // as node id
                    'field_contact' => $item['field_contact'], // as node id
                    'field_description' => $item['field_description'],
                ));
            }

        }

        return;
    }

    function parse_comment($dom, $data) {
        $comments = $this->parse_comment_on_page($dom);

        if ($comments) {
            // hardcoded
            $item = array();
            $item['node_type'] = 'zaved';
            $item['title'] = $data['title'];
            $item['title_field'] = $data['title'];
            $item['field_contact'] = $data['field_contact'];
            $item['comments'] = $comments;
            $item['field_description'] = !empty($data['field_description']) ? $data['field_description'] : '';
            $this->emit($item);
        }
    }

    function parse_comment_on_page($dom) {
        // Комментарии
        $comments = array();
        $content = $dom->find('#content', 0);
        $table = $content->find('table[cellspacing=10]', 0);
        $ctables = $table->find('tr table[cellspacing=0]');

        /* @var $ctable \simple_html_dom_node */
        foreach($ctables as $ctable) {
            // Комментарий
            $comment = array();

            // author
            $comment['author'] = array(
                'name' => $ctable->find('td', 0)->find('p b', 0)->plaintext,
                'photo' => "http://ucheba-otziv.ru" . $ctable->find('td', 0)->find('img', 0)->src,
            );

            // Создан
            $comment['created'] = $ctable->find('td', 1)->find('p', 0)->plaintext;
            $comment['created'] = $this->parse_date($comment['created']);

            // Голос. 1=Положительный отзыв, 2=Нейтральный отзыв, 3=Отрицательный отзыв
            $alt = $ctable->find('td', 1)->find('p', 1)->find('img', 0)->alt;

            if ($alt == 'Отрицательный отзыв') {
                $comment['field_vote'] = 3;
            } else if ($alt == "Положительный отзыв") {
                $comment['field_vote'] = 1;
            } else {
                $comment['field_vote'] = 2;
            }

            // Comment
            $comment['comment_body'] = trim($ctable->find('td', 1)->find('p', 1)->plaintext);
            $comment['comment_body'] = html_entity_decode($comment['comment_body']);

            $comments[] = $comment;
        }

        return $comments;
    }

    function prepare_urls() {
        for($i=0; $i<=1300; $i+=50) {
            $url = 'http://ucheba-otziv.ru/opinion/?curPos=' . $i;
            $this->add_url($url, array('parser' => self::PARSER_INDEX));
        }
    }

    function parse_date($date) {
        // '5 апреля 2013 года в 20:12'
        $monthes = array(
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        );
        $exploded = explode(' ', trim($date));

        if (count($exploded) != 6) {
            $timestamp = time();
            return $timestamp;
        }

        $day = $exploded[0];
        $month_name = $exploded[1];
        $month = $monthes[$month_name];
        $year = $exploded[2];
        $time = $exploded[5];
        list($hour, $min) = explode(':', $time);

        $timestamp = mktime($hour, $min, 0, $month, $day, $year);

        return $timestamp;
    }
}

