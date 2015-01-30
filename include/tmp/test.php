<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 24.01.15
 * Time: 14:03
 */

$file = '/home/vital/src/otziv.karo.pro/sites/default/files/grabber/events/SCANNED/Grabber_Scanners_UchebaOtziv/1421899248.1985.event.json';

$content = file_get_contents($file);

var_dump($content);

$event = json_decode($content, TRUE);

var_dump($event);

$event['file'] = $file;

var_dump($event);
