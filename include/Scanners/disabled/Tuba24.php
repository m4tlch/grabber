<?php

namespace Grabber\Scanners;

require_once(__DIR__ . '/../Registry.php');
require_once(__DIR__ . '/../Scanner.php');
require_once(__DIR__ . '/../Logger.php');
require_once(__DIR__ . '/../Downloader.php');
require_once(__DIR__ . '/../Event.php');
require_once(__DIR__ . '/../simple_html_dom.inc.php');

use Grabber\Logger;
use \Grabber\Registry;
use \Grabber\Downloader;
use \Grabber\Event;
use \Grabber\EventFactory;
use \Grabber\Scanner;


class Tuba24 extends Scanner
{
    const PARSER_INDEX = 'parse_index';
    const PARSER_PAGE = 'parse_page';

    var $getted_url_event = NULL;

    function execute() {
        $url_and_data = $this->get_url();

        if (!$url_and_data) {
            // jbcity = 457
            $url = 'http://tuba24.ru/';;
            $this->add_url($url, array('parser' => self::PARSER_INDEX));
            $url_and_data = $this->get_url();
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
                Logger::error($e->getMessage());
            }

        }
    }

    function add_url($url, $data=array()) {
        $queue = Registry::get_queue();
        $event = EventFactory::create(Event::TYPE_URL, get_called_class(), $data);
        $event['url'] = $url;
        $queue->add($event);
    }

    function get_url() {
        $queue = Registry::get_queue();
        $events = $queue->get_urls(get_called_class());

        if (empty($events)) {
            return NULL;
        }

        $random_index = rand(0, count($events)-1);
        $event = $events[$random_index];

        $this->getted_url_event = $event;

        $url = $event['url'];
        $data = $event['data'];

        return array('url' => $url, 'data' => $data);
    }

    function remove_url($url) {
        $queue = Registry::get_queue();
        $queue->remove($this->getted_url_event);
        $this->getted_url_event = NULL;
    }

    function parse($dom, $data, $url) {
        $parser = $data['parser'];

        switch($parser) {
            case self::PARSER_INDEX:
                $this->parse_index($dom, $data);
                break;

            case self::PARSER_PAGE:
                $this->parse_page($dom, $data, $url);
                break;

            default:
                throw new \Exception("Unsupported parser: '{$parser}'");
        }
    }

    function parse_index($dom, $data) {
        $content = $dom->find('.container .centercolumn', 0);

        // объявления
        $links = $content->find('.stradv a.b');

        foreach ($links as $link) {
            // save url to queue. for later parsing
            $this->add_url("http://tuba24.ru/" . trim(html_entity_decode($link->href)), array(
                'parser' => self::PARSER_PAGE,
            ));
        }

        // next page
/*
        $next = $dom->find('nav.b-pages .pages-wrapper .page-next .page-link', 0);

        if ($next->href) {
            $this->add_url("https://m.avito.ru" . trim(html_entity_decode($next->href)), array(
                    'parser' => self::PARSER_INDEX,
                ));
        }
*/
    }

    function parse_page($dom, $data, $url) {
        // hardcoded
        $item = array();
        $item['node_type'] = 'obyava';

        // content
        $content = $dom->find('.container .centercolumn', 0);

        // title
        $title = $content->find('h1', 0);
        $item['title'] = trim($title);

        // description
        $description_node = $content->find('index', 0);
        $description = trim(html_entity_decode($description_node->plaintext));
        $item['body'] = $description;

        // author
        $author = $content->find('#search_autor', 0);
        $author = trim($author->plaintext);
        //$item['body'] .= $author;

        // photo
        $photo = $content->find('div a img', 0);

        if ($photo) {
            $item['field_photo'] = $photo->src;
        } else {
            $item['field_photo'] = '';
        }

        // price
        $price_node = $content->find('span.b orange', 0);
        $price = trim(html_entity_decode($price_node->plaintext));
        $item['field_cost'] = $this->parse_cost($price);

        $this->emit($item);
    }

    function parse_cost($string) {
        $result = '';

        while ($string) {
            $char = substr($string, 0, 1);

            if (is_numeric($char) || $char == '.') {
                $result = $result . $char;
            }

            $string = substr($string, 1);
        }

        return floatval($result);
    }
}

