<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 16.11.14
 * Time: 16:22
 */

namespace Grabber;

require_once(__DIR__ . '/FileCache.php');
require_once(__DIR__ . '/Downloader.php');

use \Grabber\Downloader;

class ImageFetcher {
    function execute($url) {
        $url = trim($url);

        // local file
        if (substr(strtolower($url), 0, 7) != 'http://' && substr(strtolower($url), 0, 8) != 'https://') {
            // local file
            if (file_exists($url)) {
                return $url;
            } else {
                throw new \Exception("Local file not exists: {$url}");
            }
        }

        // remote file. use Cache
        $cache_key = $this->get_cache_key($url);
        $is_exist = FileCache::is_exists($cache_key);

        // skip already fetched
        if ($is_exist) {
            $file = FileCache::get_file_name($cache_key);
            return $file;
        }

        // fetch
        $image = file_get_contents($url, FILE_BINARY);
        //$image = Downloader::get($url, Downloader::TYPE_RAW);

        // update cache
        FileCache::set($cache_key, $image);

        // filename
        $file = FileCache::get_file_name($cache_key);

        return $file;
    }

    function get_cache_key($url) {
        $key = md5($url);
        return $key;
    }
}
