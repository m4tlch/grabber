<?php

/**
 * Implements hook_menu().
 */
function grabber_menu() {
    $items['grabber/test'] = array (
        'page callback' => 'grabber_test',
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
        'file' => 'grabber.admin.php',
    );

    $items['admin/config/administration/grabber/configurator/ajax'] = array (
        'page callback' => 'grabber_configurator_ajax',
        'delivery callback' => 'grabber_ajax_callback',  // Magic goes here
        'access arguments' => array('administer site configuration'),
        'type' => MENU_CALLBACK,
        'file' => 'grabber.admin.php',
    );

    $items['grabber/execute'] = array (
        'page callback' => 'grabber_execute',
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
    );

    $items['grabber/execute/processor/%'] = array (
        'page callback' => 'grabber_execute_processor',
        'page arguments' => array(3),
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
    );

    $items['grabber/execute/scanner/%'] = array (
        'page callback' => 'grabber_execute_scanner_all',
        'page arguments' => array(3),
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
    );

    $items['grabber/execute/scanner/%/%'] = array (
        'page callback' => 'grabber_execute_scanner',
        'page arguments' => array(3, 4),
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
    );

    $items['grabber/test/loader'] = array (
        'page callback' => 'grabber_test_loader',
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
    );

    $items['grabber/test/scanner'] = array (
        'page callback' => 'grabber_test_scanner',
        'access arguments' => array('access content'),
        'type' => MENU_CALLBACK,
    );

    $items['admin/config/administration/grabber'] = array(
        'title' => 'Grabber',
        'description' => 'Configure Grabber',
        'page callback' => 'drupal_get_form',
        'page arguments' => array('grabber_settings_form'),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );

/*
    $items['admin/config/administration/grabber/loader'] = array(
        'title' => 'Loader',
        'description' => 'Configure Grabber Loader',
        'page callback' => 'drupal_get_form',
        'page arguments' => array('grabber_loader_settings_form'),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );
*/
    $items['admin/config/administration/grabber/loader/settings'] = array(
        'title' => 'Loader settings',
        'description' => '',
        'page callback' => 'drupal_get_form',
        'page arguments' => array('grabber_types_form_loader'),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );
    $items['admin/config/administration/grabber/loader/settings/%'] = array(
        'title' => 'Loader settings for type',
        'description' => 'Configure Loader',
        'page callback' => 'grabber_loader_settings',
        'page arguments' => array(6),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );

    $items['admin/config/administration/grabber/store/clear'] = array(
        'title' => 'Clear',
        'description' => 'Clear grabber store',
        'page callback' => 'grabber_store_clear',
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );

    $items['admin/config/administration/grabber/types'] = array(
        'title' => 'Writer settings',
        'description' => '',
        'page callback' => 'drupal_get_form',
        'page arguments' => array('grabber_types_form'),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );
    $items['admin/config/administration/grabber/type/%'] = array(
        'title' => 'Writer settings for type',
        'description' => 'Configure type',
        'page callback' => 'grabber_type_fields_settings',
        'page arguments' => array(5),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );

    $items['admin/config/administration/grabber/commentwriter/settings'] = array(
        'title' => 'Comment writer settings',
        'description' => '',
        'page callback' => 'drupal_get_form',
        'page arguments' => array('grabber_types_form_comment_writer'),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );
    $items['admin/config/administration/grabber/commentwriter/settings/%'] = array(
        'title' => 'Comment writer settings for type',
        'description' => 'Configure Comment writer',
        'page callback' => 'grabber_commentwriter_settings',
        'page arguments' => array(6),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );

/*
    $items['admin/config/administration/grabber/scanners'] = array(
        'title' => 'Grabber',
        'description' => 'Configure Grabber',
        'page callback' => 'grabber_settings',
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );

    $items['admin/config/administration/grabber/scanners/%'] = array(
        'title' => 'Node type grabbers',
        'description' => 'Configure Grabber',
        'page callback' => 'drupal_get_form',
        'page arguments' => array('grabber_concrete_settings_form', 4),
        'access arguments' => array('administer site configuration'),
        'file' => 'grabber.admin.php',
    );
*/
    $items['admin/reports/grabber'] = array(
        'title' => 'Grabber log',
        'description' => 'Show grabbing process log',
        'page callback' => 'grabber_report_log',
        'access arguments' => array('administer content types'),
        'type' => MENU_NORMAL_ITEM,
        'file' => 'grabber.admin.php',
    );

    $items['admin/reports/grabber/clear'] = array(
        'title' => 'Clear Grabber log',
        'description' => 'Clear',
        'page callback' => 'grabber_report_log_clear',
        'access arguments' => array('administer content types'),
        'type' => MENU_NORMAL_ITEM,
        'file' => 'grabber.admin.php',
    );

    $items['admin/reports/grabber/events/%'] = array(
        'title' => 'Scanned',
        'description' => '',
        'page callback' => 'grabber_report_events',
        'page arguments' => array(4),
        'access arguments' => array('administer content types'),
        'type' => MENU_NORMAL_ITEM,
        'file' => 'grabber.admin.php',
    );

    return $items;
}
