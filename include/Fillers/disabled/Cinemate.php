<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.09.14
 * Time: 17:22
 */

namespace Grabber\Fillers;

require_once(__DIR__ . '/simple_html_dom.inc.php');


class Cinemate {
    function execute($title, $casts, $year) {
        $url = $this->search($title, $casts, $year);
        $item = $this->parse($url);

        return $item;
    }

    function search($title, $casts, $year) {
        $headers = "";
        $token = "yJprtQPLgjkGlAKW2B7C49zLm3sQ1xDU";

        $request = 'http://cinemate.cc/search/' . http_build_query(
                array(
                    'term' => $title,
                    'csrfmiddlewaretoken' => $token,
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

        // http://cinemate.cc/movie/152352/links/
        $html = file_get_html($url);

        $content = $html->find('#content', 0);
        $object_detail = $content->find('.object_detail', 0);

        $year = $object_detail->find('h1 small a', 0)->plaintext;

        $item['field_year'] = trim($year);

        $main = $object_detail->find('.nain', 0);
        $country = $main;
        $item['field_country'] = trim($country);
        $item['field_time'] = trim($time);

        // trailer
        $video = $content->find('.b-video-player rl:video', 0);
        $item['field_media'] = 'http://pgc.rambler.ru/store/pgc/video/' . $video->uid . '.video.109.mp4';

        // age
        $age = $info->find('.audience', 0)->title;
        $age = filter_var($age, FILTER_SANITIZE_NUMBER_INT);
        $age = $age ? ($age.'+') : '';
        $item['field_age'] = $age;

        // description
        $item['field_context'] = $content->find('.b-object-desc p', 0)->plaintext;

        return $item;
    }
}
