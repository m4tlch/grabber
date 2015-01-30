<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 18.10.14
 * Time: 18:13
 */

namespace Grabber\Fillers;


class Youtube {
    function execute($title, $casts, $year) {
        $item = array();
        $item['field_kadrs'] = array();
        $item['field_media'] = '';

        $exploded = explode(' ', trim($title));
        $exploded = array_map('urlencode', $exploded);
        $query = implode('/', $exploded);

        // trailer, poster, kadrs
        $url = 'https://gdata.youtube.com/feeds/api/videos/-/'.$query.'/Film?alt=json';
        $content = @file_get_contents($url);
        $json = json_decode($content, TRUE);

        if (!empty($json['feed']['entry'])) {

            foreach($json['feed']['entry'] as $entry) {
                foreach($entry['media$group']['media$thumbnail'] as $thumb) {
                    $item['field_kadrs'][] = $thumb['url'];
                };

                foreach($entry['media$group']['media$content'][0] as $media) {
                    if (!empty($media['type']) && $media['type'] == "application/x-shockwave-flash") {
                        $item['field_media'] = $media['url'];
                        break;
                    }
                }

                break; // first only
            }

        }
        return $item;
    }
}
