<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.09.14
 * Time: 17:22
 */

namespace Grabber\Fillers;

require_once(__DIR__ . '/../include/simple_html_dom.inc.php');


class Afisha {
    function execute($title, $casts, $year) {
        $url = $this->search($title, $casts, $year);
        $item = $this->parse($url);

        return $item;
    }

    function search($item) {
        $title = $item['title'];
        $headers = "";
        $title = str_replace('3D', '', $title);
        $title = trim($title);

        $request = 'http://www.afisha.ru/Search/?' . http_build_query(
                array(
                    'Search_str' => $title,
                    'filter' => 16,
                )
            );

        $opts = array(
            'http'=>array(
                'method' => "GET",
                'header' => $headers
            )
        );

        $context = stream_context_create($opts);

        $html = file_get_html($request, false, $context);

        if (!$html) {
            throw new \Exception("Can't get Afisha html.");
        }

        // Find all items
        $results = $html->find('.finded-item', 0);

        if (!$results) {
            throw new \Exception("Not found.");
        }

        // Get url of first
        $url = $results->find('h3 a', 0)->href;

        return $url;
    }

    function parse($url) {
        $item = array();

        // http://www.kinopoisk.ru/film/771227/
        $html = file_get_html($url);

        if (!is_object($html)) {
            throw new \Exception("Not found in afisha.ru");
        }

        $content = $html->find('#content', 0);
        $info = $content->find('.b-object-summary', 0);

        $year_country = $content->find('.creation', 0)->plaintext;
        $exploded = explode(',', $year_country);
        switch (count($exploded)) {
            case 3:
                list($country, $year, $time) = $exploded;
                break;

            case 4:
                list($country1, $country, $year, $time) = $exploded;
                break;

            default:
                list($country, $year, $time) = $exploded;
                break;

        }


        $item['field_year'] = trim($year);
        $item['field_country'] = trim($country);
        $item['field_time'] = trim($time);

        // trailer
        $video = $content->find('.b-video-player rl:video', 0);
        if ($video) {
            $item['field_media'] = 'http://pgc.rambler.ru/store/pgc/video/' . $video->uid . '.video.109.mp4';
        }

        // age
        $age = $info->find('.audience', 0)->title;
        $age = filter_var($age, FILTER_SANITIZE_NUMBER_INT);
        $age = $age ? ($age.'+') : '';
        $item['field_age'] = $age;

        // description
        $description = $content->find('.b-object-desc p', 0);
        if ($description) {
            $item['field_context'] = $description->plaintext;
        }

        return $item;
    }
}
