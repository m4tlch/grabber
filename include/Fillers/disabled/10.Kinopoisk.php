<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 17.09.14
 * Time: 17:22
 */

namespace Grabber\Fillers;

require_once(__DIR__ . '/../../include/Snoopy.class.php');


class Kinopoisk {
    var $snoopy = NULL;
    var $login = '';
    var $password = '';

    function __construct() {
        $login = variable_get('grabber_kinopoisk_login', '');
        $password = variable_get('grabber_kinopoisk_password', '');

        $this->set_login($login, $password);
    }

    function execute($item) {
        $title = $item['title'];
        $this->snoopy = new \Snoopy();
        $this->auth();
        $movie_id = $this->search($title);
        $item = $this->parse($movie_id);

        return $item;
    }

    function set_login($login, $password) {
        $this->login = $login;
        $this->password = $password;
    }

    function auth() {
        $this->snoopy->maxredirs = 2;

        //авторизация, чтобы не банили
        $post_array = array(
            'shop_user[login]' => $this->login,
            'shop_user[pass]' => $this->password,
            'shop_user[mem]' => 'on',
            'auth' => 'go',
        );

        $this->snoopy->agent = "Mozilla/5.0 (Windows; U; Windows NT 6.1; uk; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13 Some plugins";

        //отправляем данные для авторизации
        $this->snoopy->submit('http://www.kinopoisk.ru/login/', $post_array);

        // TODO check
    }

    function search($title) {
        $url = 'http://www.kinopoisk.ru/index.php';

        $vars = array(
            'level' => 7,
            'from' => 'forma',
            'result' => 'adv',
            'm_act[from]' => 'forma',
            'm_act[what]' => 'content',
            'm_act[find]' => $title, //iconv("utf-8", "windows-1251", $title),
            'm_act[content_find]' => 'film,serial',
            'first' => 'yes',
        );

        $query = http_build_query($vars);

        $old_maxredirs = $this->snoopy->maxredirs;
        $this->snoopy->maxredirs = 0;
        $this->snoopy->fetchtext($url . '?' . $query);
        $this->snoopy->maxredirs = $old_maxredirs;

        foreach($this->snoopy->headers as $header) {
            if (preg_match("/Location: ([^\s]*)/", $header, $match)) {
                $location = $match[1];
            }
        }

        if (empty($location)) {
            throw new \Exception("Empty location header");
        }

        if (strpos($location, 'http') === 0) {
            throw new \Exception("Wrong location header. Location: ('".$location."')");
        }

        if (preg_match("/\/([\d]*)\/$/", $location, $match)) {
            $movie_id = $match[1];
        } else {
            throw new \Exception("Location does not contain movie id. Location: ('".$location."')");
        }

        return $movie_id;
    }

    function parse($id){

        //$movie_info = array('kinopoisk_id' => $id);
        $movie_info = array();

        $movie_url = 'http://www.kinopoisk.ru/film/'.$id.'/';

        $cover_big_url = 'http://st.kinopoisk.ru/images/film_big/'.$id.'.jpg';
        $big_cover_headers = @get_headers($cover_big_url, 1);

        if ($big_cover_headers !== false){

            if (strpos($big_cover_headers[0], '302') !== false && !empty($big_cover_headers['Location'])){
                $movie_info['field_bgpost'] = $big_cover_headers['Location'];
            }else{
                $movie_info['field_bgpost'] = $cover_big_url;
            }
        }

        $this->snoopy->fetch('http://www.kinopoisk.ru/film/'.$id.'/');
        $page = $this->snoopy->results;

        libxml_use_internal_errors(true);
        $dom = new \DomDocument();
        $dom->loadHTML($page);
        libxml_use_internal_errors(false);

        $xpath = new \DomXPath($dom);

        // Translated name
        $node_list = $xpath->query('//*[@id="headerFilm"]/h1');

        if ($node_list !== false && $node_list->length != 0){
            $name = $this->getNodeText($node_list->item(0));
        }

        if (empty($name)){
            throw new \Exception("Movie name in '".$movie_url."' not found");
        }

        // Year
        $node_list = $xpath->query('//*[@id="infoTable"]/table/tr[1]/td[2]/div/a');

        if ($node_list !== false && $node_list->length != 0){
            $movie_info['field_year'] = $this->getNodeText($node_list->item(0));
        }

        // Duration
        $node_list = $xpath->query('//*[@id="runtime"]');

        if ($node_list !== false && $node_list->length != 0) {
            // 107 мин. / 01:47
            $minutes = (int) $this->getNodeText($node_list->item(0));
            $time = $this->minutes_to_hours($minutes);
            $movie_info['field_time'] = "{$minutes} / {$time}";
        }

        // Director
        $node_list = $xpath->query('//*[@id="infoTable"]/table/tr[4]/td[2]/a');

        if ($node_list !== false && $node_list->length != 0){
            $movie_info['field_regiser'] = $this->getNodeText($node_list->item(0));
        }

        // Actors
        $node_list = $xpath->query('//*[@id="actorList"]/ul[1]/li');

        if ($node_list !== false && $node_list->length != 0){

            $actors = array();

            foreach ($node_list as $node){
                $actors[] = $this->getNodeText($node);
            }

            if ($actors[count($actors) - 1] == '...'){
                unset($actors[count($actors) - 1]);
            }

            $movie_info['field_roles'] = implode(", ", $actors);
        }

        // Genre
        $node_list = $xpath->query('//span[@itemprop="genre"]');

        if ($node_list !== false && $node_list->length != 0) {
            $movie_info['field_ganr'] = array();

            for($i=0; $i<$node_list->length; $i++) {
                $ganr = $this->getNodeText($node_list->item($i));
                foreach(explode(',', $ganr) as $gn) {
                    $gn = trim($gn);
                    $movie_info['field_ganr'][] = $gn;
                }
            }
        }

        // Description
        //$node_list = $xpath->query('//*[@id="syn"]/tr[1]/td/table/tr[1]/td');
        $node_list = $xpath->query('//div[@itemprop="description"]');

        if ($node_list !== false && $node_list->length != 0){
            $movie_info['field_context'] = $this->getNodeText($node_list->item(0));
        }

        // Trailer
        if (preg_match("/\"trailerFile\": \"(.*)\"/", $page, $matches)) {
            $movie_info['field_media'] = 'http://kp.cdn.yandex.net//trailers/' . $matches[1];
        }

        // Age limit
        $node_list = $xpath->query('//div[contains(@class, "ageLimit")]');

        if ($node_list !== false && $node_list->length != 0){
            $class = $node_list->item(0)->attributes->getNamedItem('class')->nodeValue;
            $movie_info['field_age'] = substr($class, strrpos($class, 'age')+3);
            if ($movie_info['field_age']){
                $movie_info['field_age'] .= '+';
            }
        }

        // kadrs
        try {
            $movie_info['field_kadrs'] = $this->get_kadrs($id);
        } catch (\Exception $e) {
            //
        }

        // posters
        if (!empty($movie_info['field_bgpost'])) {
            $movie_info['field_poster'] = $movie_info['field_bgpost'];
        } else {
            try {
                $posters = $this->get_posters($id);
                if (!empty($posters)) {
                    $movie_info['field_poster'] = $posters[0];
                }
            } catch (\Exception $e) {
                $movie_info['field_poster'] = $movie_info['field_bgpost'];
            }
        }

        // Rating MPAA
        /*
                $node_list = $xpath->query('//td[contains(@class, "rate_")]');
                if ($node_list !== false && $node_list->length != 0){
                    $class = $node_list->item(0)->attributes->getNamedItem('class')->nodeValue;
                    $movie_info['rating_mpaa'] = strtoupper(substr($class, 5));

                    if ($movie_info['rating_mpaa'] == 'PG13'){
                        $movie_info['rating_mpaa'] = 'PG-13';
                    }elseif($movie_info['rating_mpaa'] == 'NC17'){
                        $movie_info['rating_mpaa'] = 'NC-17';
                    }
                }
        */

        // Kinopoisk rating
        $movie_info['field_rating'] = "<img src='http://rating.kinopoisk.ru/$id.gif' border='0'>";


        // IMDB rating
        // <img class="imdb_informer" title="Actual IMDB rating for Interstellar 2014" src="http://imdb.snick.ru/ratefor/03/tt0816692.png" border="0"/>

        return $movie_info;
    }

    function get_kadrs($id) {
        $url = 'http://www.kinopoisk.ru/film/'.$id.'/stills/';
        $this->snoopy->fetch($url);
        $page = $this->snoopy->results;

        libxml_use_internal_errors(true);
        $dom = new \DomDocument();
        $dom->loadHTML($page);
        libxml_use_internal_errors(false);

        $xpath = new \DomXPath($dom);

        //'.fotos td a'
        $node_list = $xpath->query('//table[contains(@class, "fotos")]/tr/td/a');

        $kadrs = array();

        if ($node_list !== false && $node_list->length != 0) {
            foreach ($node_list as $node) {
                $href = $node->attributes->getNamedItem('href')->nodeValue;
                $url = 'http://www.kinopoisk.ru' . $href;

                $kadr = $this->get_one_kadr($url);

                if ($kadr) {
                    $kadrs[] = $kadr;
                }
            }
        }

        return $kadrs;
    }

    function get_one_kadr($url) {
        $this->snoopy->fetch($url);
        $page = $this->snoopy->results;

        libxml_use_internal_errors(true);
        $dom = new \DomDocument();
        $dom->loadHTML($page);
        libxml_use_internal_errors(false);

        $xpath = new \DomXPath($dom);

        //'#main_table img
        $node_list = $xpath->query('//table[@id="main_table"]/tr/td/img');

        foreach ($node_list as $node){
            $src = $node->attributes->getNamedItem('src')->nodeValue;
            return $src;
        }

        return FALSE;
    }

    function get_posters($id) {
        $url = 'http://www.kinopoisk.ru/film/'.$id.'/posters/';
        $this->snoopy->fetch($url);
        $page = $this->snoopy->results;

        libxml_use_internal_errors(true);
        $dom = new \DomDocument();
        $dom->loadHTML($page);
        libxml_use_internal_errors(false);

        $xpath = new \DomXPath($dom);

        //'.fotos td a'
        $node_list = $xpath->query('//table[contains(@class, "fotos")]/tr/td/a');

        $posters = array();

        if ($node_list !== false && $node_list->length != 0) {
            foreach ($node_list as $node) {
                $href = $node->attributes->getNamedItem('href')->nodeValue;
                $url = 'http://www.kinopoisk.ru' . $href;
                $poster = $this->get_one_kadr($url);

                if ($poster) {
                    $posters[] = $poster;
                }
                break; // only one
            }
        }

        return $posters;
    }

    function getNodeText($node){

        $text = html_entity_decode($node->nodeValue);

        //$text = str_replace('&nbsp;', ' ', $text);

        $rules = array(
            "/\x{0085}/u" => "...",
            "/(\s+)/" => " ",
            "/\n/" => ""
        );

        $text = trim(preg_replace(array_keys($rules), array_values($rules), $text));

        return $text;
    }

    function settings() {
        $form['grabber_kinopoisk_login'] = array(
            '#title' => 'Kinopoisk login',
            '#type' => 'textfield',
            '#size' => '30',
            '#default_value' => variable_get('grabber_kinopoisk_login', ''),
        );

        $form['grabber_kinopoisk_password'] = array(
            '#title' => 'Kinopoisk password',
            '#type' => 'textfield',
            '#size' => '30',
            '#default_value' => variable_get('grabber_kinopoisk_password', ''),
        );

        return $form;
    }

    function minutes_to_hours($minutes) {
        $hours = floor($minutes / 60);
        $rest = $minutes - $hours * 60;
        return  "{$hours}:{$rest}";
    }
}

/* test
 $x = new Kinopoisk();
$x->set_login('adisk', 'q1234567890');
$result = $x->execute(array('title' => 'История дельфина 2'));
var_dump($result);

function variable_get($a) {
    return '';
}
*/
