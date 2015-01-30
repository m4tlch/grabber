<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 08.01.15
 * Time: 10:54
 */

namespace Grabber;

require_once('ProxyList.php');
require_once('UseragentList.php');
require_once('Snoopy.class.php');
require_once('simple_html_dom.inc.php');

use \Grabber\ProxyList;
use \Grabber\UseragentList;

class NetworkIsDownException extends \Exception {};

class Downloader {
    const TYPE_RAW = "RAW";
    const TYPE_JSON = "JSON";
    const TYPE_HTML_DOM = "HTML_DOM";
    const TYPE_XML = "XML";

    static $is_use_proxy = true;
    static $is_use_curl = true;
    static $connection_timeout = 7; //seconds

    static function get($url, $type=self::TYPE_RAW) {
        $URI_PARTS = parse_url($url);

        if ($URI_PARTS["scheme"] == 'https') {
            $result = file_get_html($url);
            return $result;
        }

        // get proxy
        if (self::$is_use_proxy) {
            try {
                $proxy = ProxyList::get();

            } catch (\Exception $e) {
                throw new \Exception("No proxy: " . $e->getMessage());
            }
        } else {
            $proxy = NULL;
        }

        // get user agent
        $useragent = UseragentList::get();

        // fetch
        if (self::$is_use_curl) {
            $content = self::get_curl($url, $proxy, $useragent);
        } else {
            $content = self::get_snoopy($url, $proxy, $useragent);
        }

        // parse
        try {
            $result = self::parse($content, $type);

        } catch (\Exception $e) {
            throw new \Exception("Error when parse url: {$url}: " . $e->getMessage());
        }

        return $result;
    }

    static function post($url) {
        throw new \Exception("Not implemented");
    }

    static function submit($url) {
        throw new \Exception("Not implemented");
    }

    static function check_for_errors($content, $response_code, $error) {
        if ($error || (strstr($response_code, '200') === false)) {
            // if network card switched off200
            if (strstr($error, ' (101)') !== FALSE // connection failed | Network is unreachable
                || strstr($error, ' (100)') !== FALSE // Network is down
                || strstr($error, 'php_network_getaddresses: getaddrinfo failed') !== FALSE // Name not resolved to IP (Network is down)
            ) {
                throw new NetworkIsDownException("Network is down: {$error}");
            }
            throw new \Exception("{$error}: Response code: " . (empty($response_code) ? '(empty)' : $response_code));
        }

        if (!$content) {
            throw new \Exception('No content.');
        }
    }

    static function parse($data, $data_type) {
        switch ($data_type) {
            case self::TYPE_RAW:
                $result = $data;
                break;

            case self::TYPE_HTML_DOM:
                $result = str_get_html($data);

                if (!$result) {
                    throw new \Exception('Can not parse html');
                }

                break;

            case self::TYPE_JSON:
                $result = json_decode($data);

                if ($result === false) {
                    throw new \Exception('Can not parse json');
                }

                break;

            case self::TYPE_XML:
                $result = str_get_html($data);
                break;

            default:
                throw new \Exception("Unsupported type: {$data_type}");
        }

        return $result;
    }

    static function get_curl($url, $proxy, $useragent) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);

        // setup proxy
        if ($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy['host'] . ':' . $proxy['port']);
        }

        // setup user agent
        if ($useragent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        }

        // timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connection_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$connection_timeout);

        //curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $content = curl_exec($ch);

        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);

        if (//$errno == 7 // CURLE_COULDNT_CONNECT
            $errno == 5 // CURLE_COULDNT_RESOLVE_PROXY
        ) {
            sleep(3);
            throw new NetworkIsDownException("Network is down. (code): {$errno}");
        }

        if ($errno == 18) { // CURLE_PARTIAL_FILE
            //return '';
        }

        if ($errno == 28  // CURLE_OPERATION_TIMEDOUT
            || $errno == 56  //CURLE_RECV_ERROR
            || ($errno == 7 && strstr($error, 'Connection refused') != false)  // CURLE_COULDNT_CONNECT
            || ($errno == 7 && strstr($error, 'No route to host'))
            || ($response_code == 404 && empty($error)) // Not Found
            || ($response_code == 407 && empty($error)) // Proxy Authentication Required
        ) {
            ProxyList::add_to_blacklist($proxy['host'], $proxy['port']);
            Logger::log('Blacklisted proxy: ' . $proxy['host'] . ':' . $proxy['port']);
        }

        if ($response_code == '503') { //  Service Unavailable
            ProxyList::add_to_blacklist($proxy['host'], $proxy['port']);
            Logger::log('Blacklisted proxy: ' . $proxy['host'] . ':' . $proxy['port']);
        }

        if ($response_code == '504') { //  Gateway Timeout
            ProxyList::add_to_blacklist($proxy['host'], $proxy['port']);
            Logger::log('Blacklisted proxy: ' . $proxy['host'] . ':' . $proxy['port']);
        }

        if ($response_code != '200') {
            throw new \Exception("Not fetched: {$url}: (code: errno: error): {$response_code}: {$errno}: {$error}");
        }

        if (!empty($error)) {
            throw new \Exception("Not fetched: {$url}: (code: errno: error): {$response_code}: {$errno}: {$error}");
        }

        return $content;
    }

    static function get_snoopy($url, $proxy, $useragent) {
        $snoopy = new \Snoopy();

        $snoopy->_fp_timeout = self::$connection_timeout;
        $snoopy->read_timeout = self::$connection_timeout;

        // setup proxy
        if ($proxy) {
            $snoopy->proxy_host = $proxy['host'];
            $snoopy->proxy_port = $proxy['port'];
        }

        // setup user agent
        if ($useragent) {
            $snoopy->agent = $useragent;
        }

        // fetch
        $snoopy->fetch($url);

        $content = $snoopy->results;

        // validate
        try {
            self::check_for_errors($snoopy->results, $snoopy->response_code, $snoopy->error);

        } catch(NetworkIsDownException $e) {
            sleep(3);
            throw new \Exception("Network is down: " . $e->getMessage());

        } catch(\Exception $e) {
            // add proxy to black list.
            if (self::$is_use_proxy) {
                ProxyList::add_to_blacklist($proxy['host'], $proxy['port']);
                Logger::log('Blacklisted proxy: ' . $proxy['host'] . ':' . $proxy['port']);
            }
            throw new \Exception("Not fetched: {$url}: " . $e->getMessage());
        }

        return $content;
    }
}
