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


class Avito extends Scanner
{
    const PARSER_INDEX = 'parse_index';
    const PARSER_PAGE = 'parse_page';

    var $getted_url_event = NULL;

    function execute() {
        $url_and_data = $this->get_url();

        if (!$url_and_data) {
            $url = 'https://m.avito.ru/bolshaya_irba';;
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
        $content = $dom->find('.b-content-main', 0);

        // объявления
        $links = $content->find('.b-item a.item-link');

        foreach ($links as $link) {
            // save url to queue. for later parsing
            $this->add_url("https://m.avito.ru" . trim(html_entity_decode($link->href)), array(
                'parser' => self::PARSER_PAGE,
            ));
        }

        // next page
        $next = $dom->find('nav.b-pages .pages-wrapper .page-next .page-link', 0);

        if ($next->href) {
            $this->add_url("https://m.avito.ru" . trim(html_entity_decode($next->href)), array(
                    'parser' => self::PARSER_INDEX,
                ));
        }
    }

    function parse_page($dom, $data, $url) {
        // hardcoded
        $item = array();
        $item['node_type'] = 'obyava';

        // content
        $content = $dom->find('.b-content-main .b-single-item', 0);
        $photo = $content->find('.photo-container img.photo-self', 0);

        if ($photo) {
            $item['field_photo'] = 'http:' . $photo->src;
        } else {
            $item['field_photo'] = '';
        }

        // title
        $title_nodes = $content->find('.single-item-header .semantic-text');
        $titles = array();

        foreach($title_nodes as $node) {
            $title = trim($node->plaintext);

            if (empty($title)) {
                continue;
            }

            $titles[] = $title;
        }

        if (empty($titles)) {
            $title = $content->find('.single-item-header', 0)->plaintext;
            $titles[] = trim($title);
        }

        $item['title'] = implode(' ', $titles);

        // price
        $price_node = $content->find('.single-item-info .info-price .price-value', 0);
        $price = trim(html_entity_decode($price_node->plaintext));
        $item['field_cost'] = $this->parse_cost($price);

        // description
        $description_node = $content->find('.single-item-description .description-preview-wrapper', 0);
        $description = trim(html_entity_decode($description_node->plaintext));
        $item['body'] = $description;
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

