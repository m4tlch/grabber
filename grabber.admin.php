<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 20.09.14
 * Time: 21:16
 */

require_once('include/NodeHelper.php');
require_once('include/Settings.php');
require_once('include/Store.php');
require_once('include/Configurator.php');
require_once('include/TaxonomyHelper.php');
require_once('include/Registry.php');

function grabber_settings_form($form, &$form_state) {
    $form = array();
    $types = array();

    foreach(node_type_get_types() as $id=>$type) {
        $types[$id] = $type->name;
    }

    // node author
    $users = entity_load('user');
    $users_as_options = array('0' => t('Guest'));

    foreach($users as $user) {
        $users_as_options[$user->uid] = $user->name;
    }

    // settings elements
    $form['map'] = array(
        '#type' => 'markup',
        '#markup' => grabber_settings_map(),
    );

    $form['splitter1'] = array(
        '#type' => 'markup',
        '#markup' => '<br>',
    );


    /* in grabber
    $form['grabber_node_type'] = array(
        '#title' => 'Node type',
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => 'happy',
    );
    */

    $form['set1'] = array(
        '#type' => 'fieldset',
    );

    $form['set1']['grabber_scan_interval'] = array(
        '#title' => 'Scan interval',
        '#type' => 'select',
        '#options' => array(
            '1' => 'каждый час',
            '2' => '12 раз в день',
            '3' => '8 раз в день',
            '4' => '6 раз в день',
            '6' => '4 раза в день',
            '12' => '2 раза в день',
            '24' => '1 раз в день',
        ),
        '#default_value' => variable_get('grabber_scan_interval', '24'),
    );

    $form['set1']['grabber_author'] = array(
        '#title' => t('Author'),
        '#type' => 'select',
        '#options' => $users_as_options,
        '#default_value' => variable_get('grabber_author', '0'),
    );


    /*
        $form['set2'] = array(
            '#type' => 'fieldset',
        );

        $form['set2']['grabber_kinopoisk_login'] = array(
            '#title' => 'Kinopoisk login',
            '#type' => 'textfield',
            '#size' => '30',
            '#default_value' => variable_get('grabber_kinopoisk_login', ''),
        );

        $form['set2']['grabber_kinopoisk_password'] = array(
            '#title' => 'Kinopoisk password',
            '#type' => 'textfield',
            '#size' => '30',
            '#default_value' => variable_get('grabber_kinopoisk_password', ''),
        );
    */
    $form['splitter'] = array(
        '#type' => 'markup',
        '#markup' => '<br>',
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
    );

    return $form;
}

function grabber_settings_map() {
    $module_path = drupal_get_path('module', 'grabber');
    drupal_add_css($module_path . '/css/admin.css', 'file');

    // scanners
    $collector = \Grabber\Registry::get_collector();
    $scanners = $collector->get_scanners();
    $scanner1 = array_shift($scanners);
    $scanner2 = array_shift($scanners);

    // events counters
    $queue = \Grabber\Registry::get_queue();
    $scanned_count = count($queue->get_scanned());
    $filled_count = count($queue->get_filled());
    $optimized_count = count($queue->get_optimized());
    $prefetcher_count = count($queue->get_images_fetched());
    $loaded_count = count($queue->get_loaded());

    // error counter
    $log_errors_count = \Grabber\Logger::get_errors_count();

    // semaphores
    $semaphores = \Grabber\Registry::get_semaphore();
    $loader_executed = $semaphores->is_set('Processor-Loader');
    $prefetcher_executed = $semaphores->is_set('Processor-Prefetcher');
    $filler_executed = $semaphores->is_set('Processor-Filler');
    $optimizer_executed = $semaphores->is_set('Processor-Optimizer');
    $collector_executed = $semaphores->is_set('Processor-Collector');

    ob_start();
    ?>

    <div id="grabber-map">
        <div class="object" id="grabber-map-loader"> Loader
            <?php if($loaded_count): ?>
                <div class="counter counter-1"> <?= $loaded_count ?> </div>
            <?php endif; ?>

            <?php if ($loader_executed): ?>
                <div class="indicator indicator-executed">&nbsp;</div>
            <?php endif; ?>

            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/processor/Loader") ?></div>
                <div class="menu-item"><?= l('Содержимое', "admin/reports/grabber/events/" . \Grabber\Event::TYPE_LOADED) ?></div>
                <div class="menu-item"><?= l('Настройки', "admin/config/administration/grabber/loader/settings") ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-prefetcher"> Prefetcher
            <?php if ($prefetcher_count): ?>
                <div class="counter counter-1"> <?= $prefetcher_count ?> </div>
            <?php endif; ?>

            <?php if ($prefetcher_executed): ?>
                <div class="indicator indicator-executed">&nbsp;</div>
            <?php endif; ?>

            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/processor/Prefetcher") ?></div>
                <div class="menu-item"><?= l('Содержимое', "admin/reports/grabber/events/" . \Grabber\Event::TYPE_IMAGES_FETCHED) ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-filler"> Filler
            <?php if($filled_count): ?>
                <div class="counter counter-1"> <?= $filled_count ?> </div>
            <?php endif; ?>

            <?php if ($filler_executed): ?>
                <div class="indicator indicator-executed">&nbsp;</div>
            <?php endif; ?>

            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/processor/Filler") ?></div>
                <div class="menu-item"><?= l('Содержимое', "admin/reports/grabber/events/" . \Grabber\Event::TYPE_FILLED) ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-optimizer"> Optimizer
            <?php if($optimized_count): ?>
                <div class="counter counter-1"> <?= $optimized_count ?> </div>
            <?php endif; ?>

            <?php if ($optimizer_executed): ?>
                <div class="indicator indicator-executed">&nbsp;</div>
            <?php endif; ?>

            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/processor/Optimizer") ?></div>
                <div class="menu-item"><?= l('Содержимое', "admin/reports/grabber/events/" . \Grabber\Event::TYPE_OPTIMIZED) ?></div>
                <div class="menu-item"><?= l('Настройки', "admin/config/administration/grabber/loader/settings") ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-collector"> Collector
            <?php if ($scanned_count): ?>
                <div class="counter counter-1"> <?= $scanned_count ?> </div>
            <?php endif; ?>

            <?php if ($collector_executed): ?>
                <div class="indicator indicator-executed">&nbsp;</div>
            <?php endif; ?>

            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/processor/Collector") ?></div>
                <div class="menu-item"><?= l('Содержимое', "admin/reports/grabber/events/" . \Grabber\Event::TYPE_SCANNED) ?></div>
            </div>
        </div>


        <div class="object" id="grabber-map-logger"> Logger
            <?php if($log_errors_count): ?>
                <div class="counter counter-1 counter-error"> <?= $log_errors_count ?> </div>
            <?php endif; ?>

            <div class="menu">
                <div class="menu-item"><?= l('Журнал', "admin/reports/grabber") ?></div>
                <div class="menu-item"><?= l('Очистить', "admin/reports/grabber/clear") ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-store"> Store
            <div class="menu">
                <div class="menu-item"><?= l('Очистить', "admin/config/administration/grabber/store/clear") ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-writer"> Writer
            <div class="menu">
                <div class="menu-item"><?= l('Настройки', "admin/config/administration/grabber/types") ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-comment-writer"> Comment Writer
            <div class="menu">
                <div class="menu-item"><?= l('Настройки', "admin/config/administration/grabber/commentwriter/settings") ?></div>
            </div>
        </div>


        <!-- Scanners -->
        <div class="object" id="grabber-map-Scanner1"> <?= $scanner1 ?>
            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/scanner/{$scanner1}") ?></div>
            </div>
        </div>

        <div class="object" id="grabber-map-Scanner2"> <?= $scanner2 ?>
            <?php if ($scanner2): ?>
            <div class="menu">
                <div class="menu-item"><?= l('Запустить', "grabber/execute/scanner/{$scanner2}") ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="object" id="grabber-map-more"> <?= !empty($scanners) ? '...' : '' ?>
            <div id="grabber-scanners-menu">
            <?php foreach($scanners as $scanner): ?>
                <div class="object"> <?= $scanner ?>
                    <div class="menu">
                        <div class="menu-item"><?= l('Запустить', "grabber/execute/scanner/{$scanner}") ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <hr>

    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

function grabber_settings_form_submit($form, &$form_state) {
    //$grabber_node_type = $form_state['values']['grabber_node_type'];
    //variable_set('grabber_node_type', $grabber_node_type);

    $grabber_scan_interval = $form_state['values']['grabber_scan_interval'];
    variable_set('grabber_scan_interval', $grabber_scan_interval);

    $grabber_author = $form_state['values']['grabber_author'];
    variable_set('grabber_author', $grabber_author);

/*
    $grabber_kinopoisk_login = $form_state['values']['grabber_kinopoisk_login'];
    variable_set('grabber_kinopoisk_login', $grabber_kinopoisk_login);

    $grabber_kinopoisk_password = $form_state['values']['grabber_kinopoisk_password'];
    variable_set('grabber_kinopoisk_password', $grabber_kinopoisk_password);
*/
}

function grabber_suggest_field($field, $all_possible) {
    // 1. equal
    foreach($all_possible as $possible) {
        if ($field == $possible) {
            return $possible;
        }
    }

    // 2. by word
    foreach($all_possible as $possible) {
        $exploded = explode('_', $possible);

        if (in_array($field, $exploded)) {
            return $possible;
        }

        $exploded = explode('-', $possible);

        if (in_array($field, $exploded)) {
            return $possible;
        }

        // reverse
        $exploded = explode('_', $field);

        if (in_array($possible, $exploded)) {
            return $possible;
        }

        $exploded = explode('-', $field);

        if (in_array($possible, $exploded)) {
            return $possible;
        }

    }

    // 3. contains
    foreach($all_possible as $possible) {
        if ($field && $possible && stristr($field, $possible) !== FALSE) {
            return $possible;
        }

        //reverse
        if ($possible && $field && stristr($possible, $field) !== FALSE) {
            return $possible;
        }
    }

    // 4. none
    return  '';
}

function grabber_report_events($type) {
    $fields = array();
    $rows = array();

    $queue = \Grabber\Registry::get_queue();
    switch ($type) {
        case \Grabber\Event::TYPE_SCANNED:
            $events = $queue->get_scanned();
            break;

        case \Grabber\Event::TYPE_OPTIMIZED:
            $events = $queue->get_optimized();
            break;

        case \Grabber\Event::TYPE_FILLED:
            $events = $queue->get_filled();
            break;

        case \Grabber\Event::TYPE_IMAGES_FETCHED:
            $events = $queue->get_images_fetched();
            break;

        case \Grabber\Event::TYPE_LOADED:
            $events = $queue->get_loaded();
            break;

        default:
            throw new \Exception("Unsupported event type: {$type}");
    }

    // get all fields
    foreach($events as $event) {
        foreach(array_keys($event['data']) as $key) {
            if (!in_array($key, $fields)) {
                $fields[] = $key;
            }
        }
    }

    foreach ($events as $event) {
        $row = array();

        foreach($fields as $field) {
            if (empty($event['data'][$field])) {
                $row[$field] = '';
            } else {
                $value = $event['data'][$field];

                if (is_string($value)) {
                    if (strlen($value) > 60) {
                        $row[$field] = mb_substr($value, 0, 60) . "...";
                    } else {
                        $row[$field] = $value;
                    }

                } elseif (is_array($value)) {
                    switch (count($value)) {
                        case 0:
                            $row[$field] = '';
                            break;

                        case 1:
                            reset($value);
                            $row[$field] = key($value) . ' = ' . grabber_mixed_to_string(current($value));
                            break;

                        case 2:
                            reset($value);
                            $row[$field] = key($value) . ' = ' . grabber_mixed_to_string(current($value)) . '<br>';
                            next($value);
                            $row[$field] .= key($value) . ' = ' . grabber_mixed_to_string(current($value));
                            break;

                        case 3:
                            reset($value);
                            $row[$field] = key($value) . ' = ' . grabber_mixed_to_string(current($value)) . '<br>';
                            next($value);
                            $row[$field] .= key($value) . ' = ' . grabber_mixed_to_string(current($value)) . '<br>';
                            next($value);
                            $row[$field] .= key($value) . ' = ' . grabber_mixed_to_string(current($value));
                            break;

                        default:
                            reset($value);
                            $row[$field] = key($value) . ' = ' . grabber_mixed_to_string(current($value)) . '<br>';
                            $row[$field] .= '...<br>';
                            end($value);
                            $row[$field] .= key($value) . ' = ' . grabber_mixed_to_string(current($value));
                            break;
                    }

                } else {
                    $row[$field] = $value;
                }
            }
        }

        $row[] = $event['source'];
        $row[] = $event['file'];

        $rows[]['data'] = $row;
    }

    $fields[] = 'source';
    $fields[] = 'file';

    $output = theme('table', array('header' => $fields, 'rows' => $rows, 'empty' => 'No entries'));

    return $output;
}

function grabber_mixed_to_string($array) {
    return is_array($array) ? '[...]' : $array;
}

function grabber_report_log() {
    $data = db_query('SELECT * FROM {grabber_log} ORDER BY id DESC');

    $header = array(t('Time'), t('Type'), t('Message'));
    $rows = array();

    foreach ($data as $dat) {
        $rows[]['data'] = array(
            date('Y-m-d H:i:s', $dat->created),
            $dat->type,
            $dat->message
        );
    }

    $output =
        '<div>' . l(t('Clear log'), 'admin/reports/grabber/clear') . '</div>'
        . theme('table', array('header' => $header, 'rows' => $rows, 'empty' => 'No log entries'));

    return $output;
}

function grabber_report_log_clear() {
    \Grabber\Logger::clear();
    drupal_goto('/admin/reports/grabber');
}

function grabber_test() {
    //$info = \Grabber\NodeHelper::get_node_info('node', \Grabber\NodeHelper::get_entity_type($entity));
    //$info = \Grabber\NodeHelper::get_node_info('entity', 'comment');
    $info = \Grabber\NodeHelper::get_node_info('comment', NULL);
    var_dump($info);
    die();
}

function grabber_loader_settings($entity_type) {
    global $base_url;

    $module_path = drupal_get_path('module', 'grabber');
    drupal_add_css($module_path . '/css/admin.css', 'file');
    drupal_add_css($module_path . '/css/widgets.css', 'file');
    drupal_add_js($module_path . '/js/jquery.json-2.4.js', 'file');
    drupal_add_js($module_path . '/js/widgets.js', 'file');

    require_once(__DIR__ . '/include/Settings/Chain.php');

    $filter = new \Grabber\NodeFilter($entity_type);
    \Grabber\Configurator::execute($filter, array('entity_type'=>$entity_type));

    try {
        $settings_panel = \Grabber\Configurator::get_settings_panel($filter);

    } catch (\Exception $e) {
        // no settings for loader
        return '';
    }

    $properties = (array)$settings_panel;
    $rows = array();
    $by_propery = array();

    foreach($properties as $property=>$settings_widget) {
        $settings_widget->url($base_url . '/admin/config/administration/grabber/configurator/ajax?' . http_build_query(
                array(
                    'class'=>get_class($filter),
                    'groups[entity_type]'=>$entity_type,
                    'property'=>$property,
                ))
        );

        $by_propery[$property] = $settings_widget->render();
    }

    // to table
    $header = array(t('Field'), t('Settings'));

    foreach($by_propery as $property=>$html) {
        $label = !empty($properties[$property]->title) ? $properties[$property]->title : $property;

        $rows[]['data'] = array(
            $label,
            $html
        );
    }

    $html = theme('table', array('header' => $header, 'rows' => $rows, 'empty' => ''));

    return $html;
}

function grabber_commentwriter_settings($entity_type) {
    global $base_url;

    $module_path = drupal_get_path('module', 'grabber');
    drupal_add_css($module_path . '/css/admin.css', 'file');
    drupal_add_css($module_path . '/css/widgets.css', 'file');
    drupal_add_js($module_path . '/js/jquery.json-2.4.js', 'file');
    drupal_add_js($module_path . '/js/widgets.js', 'file');

    require_once(__DIR__ . '/include/Settings/Chain.php');

    $filter = new \Grabber\CommentFilter($entity_type);
    \Grabber\Configurator::execute($filter, array('entity_type'=>$entity_type));

    try {
        $settings_panel = \Grabber\Configurator::get_settings_panel($filter);

    } catch (\Exception $e) {
        // no settings for loader
        return '';
    }

    $properties = (array)$settings_panel;
    $rows = array();
    $by_propery = array();

    foreach($properties as $property=>$settings_widget) {
        $settings_widget->url($base_url . '/admin/config/administration/grabber/configurator/ajax?' . http_build_query(
                array(
                    'class'=>get_class($filter),
                    'groups[entity_type]'=>$entity_type,
                    'property'=>$property,
                ))
        );

        $by_propery[$property] = $settings_widget->render();
    }

    // to table
    $header = array(t('Field'), t('Settings'));

    foreach($by_propery as $property=>$html) {
        $label = !empty($properties[$property]->title) ? $properties[$property]->title : $property;

        $rows[]['data'] = array(
            $label,
            $html
        );
    }

    $html = theme('table', array('header' => $header, 'rows' => $rows, 'empty' => ''));

    return $html;
}

function grabber_types_form($form, &$form_state) {
    $types = node_type_get_types();
    // ksort($types);
    $rows = array();
    $header = array(t('Тип Материала'));

    foreach($types as $id=>$type) {
        $rows[]['data'] = array(
            l($type->name, 'admin/config/administration/grabber/type/' . $id),
        );
    }

    $form['types'] = array(
        '#type' => 'markup',
        '#markup' => theme('table', array('header' => $header, 'rows' => $rows, 'empty' => 'No fields')),
    );

    return $form;
}

function grabber_types_form_loader($form, &$form_state) {
    $types = node_type_get_types();
    // ksort($types);
    $rows = array();
    $header = array(t('Тип Материала'));

    foreach($types as $id=>$type) {
        $rows[]['data'] = array(
            l($type->name, 'admin/config/administration/grabber/loader/settings/' . $id),
        );
    }

    $form['types'] = array(
        '#type' => 'markup',
        '#markup' => theme('table', array('header' => $header, 'rows' => $rows, 'empty' => 'No fields')),
    );

    return $form;
}

function grabber_types_form_comment_writer($form, &$form_state) {
    $types = node_type_get_types();
    // ksort($types);
    $rows = array();
    $header = array(t('Тип Материала'));

    foreach($types as $id=>$type) {
        $rows[]['data'] = array(
            l($type->name, 'admin/config/administration/grabber/commentwriter/settings/' . $id),
        );
    }

    $form['types'] = array(
        '#type' => 'markup',
        '#markup' => theme('table', array('header' => $header, 'rows' => $rows, 'empty' => 'No fields')),
    );

    return $form;
}

function grabber_type_fields_settings($entity_type) {
    global $base_url;
    $html = '';
    $rows = array();
    $by_field = array();
    $reserved = \Grabber\NodeHelper::get_reserved_fields();

    // get node fields
    $info = \Grabber\NodeHelper::get_node_info('node', $entity_type);

    // collect all settings
    foreach ($info as $field_name=>$big_info) {
        if (!array_key_exists($field_name, $info) || in_array($field_name, $reserved)) {
            continue;
        }

        if ($field_name == 'field_viewsfield') {
            continue;
        }

        $writer = \Grabber\WriterFactory::get('node',  $entity_type, $field_name);
        \Grabber\Configurator::execute($writer, array('entity_type'=>$entity_type, 'field_name'=>$field_name));

        try {
            $settings_panel = \Grabber\Configurator::get_settings_panel($writer);
        } catch (\Exception $e) {
            // no settings for writer
            continue;
        }

        $properties = (array)$settings_panel;

        foreach($properties as $property=>$settings_widget) {
            $settings_widget->url($base_url . '/admin/config/administration/grabber/configurator/ajax?' . http_build_query(
                    array(
                        'class'=>get_class($writer),
                        'groups[entity_type]'=>$entity_type,
                        'groups[field_name]'=>$field_name,
                        'property'=>$property,
                    ))
            );
            $by_field[$field_name][$property] = $settings_widget->render();
        }

    };

    // reorganize settings. group it
    $all_properties = array();

    foreach($by_field as $field=>$by_property) {
        foreach($by_property as $property=>$data) {
            if (!in_array($property, $all_properties)) {
                $all_properties[] = $property;
            }
        }
    }

    // header
    $header = array(t('Field'));
    foreach($all_properties as $property) {
        $header[] = !empty($properties[$property]->title) ? $properties[$property]->title : $property;
    }

    // to table
    foreach($by_field as $field_name=>$by_property) {
        $field_label = $info[$field_name]['label'];
        $row = array($field_label);

        foreach($all_properties as $property) {
            if (empty($by_field[$field_name][$property])) {
                $row[] = '';
            } else {
                $row[] = $by_field[$field_name][$property];
            }
        }

        $rows[]['data'] = $row;
    }

    $html = theme('table', array('header' => $header, 'rows' => $rows, 'empty' => ''));


    return $html;
}

function grabber_store_clear() {
    $store = \Grabber\Registry::get_scanner_store();
    $store->clear();
    drupal_goto('admin/config/administration/grabber');
}

function grabber_configurator_ajax() {
    $class = $_REQUEST['class'];
    $groups = $_REQUEST['groups'];
    $property = $_REQUEST['property'];
    $value = $_REQUEST['value'];

    try {
        \Grabber\Configurator::set_property_value($class, $groups, $property, $value);
        $response = \Grabber\Configurator::get_response_ok();
    } catch (Exception $e) {
        $response = \Grabber\Configurator::get_response_fail();
    }

    return $response;
}

function grabber_ajax_callback($page_callback_result) {
    print $page_callback_result;
}
