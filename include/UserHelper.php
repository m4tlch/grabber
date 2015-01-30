<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 06.01.15
 * Time: 11:32
 */

namespace Grabber;


class UserHelper {
    static function create($user_name, $user_photo) {
        $newUser = array(
            'name' => $user_name,
                'pass' => 'password', // note: do not md5 the password
            'mail' => 'stub@local.ru',
            'status' => 0,
            'init' => 'email address'
        );
        //$newUser['field_first_name'][LANGUAGE_NONE][0]['value'] = 'First Name';
        //$newUser['field_last_name'][LANGUAGE_NONE][0]['value'] = 'Last Name';
        $user = user_save(null, $newUser);

        return $user;
    }

    static function get_by_name($name) {
        $user = user_load_by_name($name);
        return $user;
    }

    static function get_author($author) {
        // Check for empty
        if (empty($author) || (is_array($author) && empty($author['name']))) {
            return 0;
        }

        $user_name = is_string($author) ? $author : $author['name'];
        $user_photo = is_array($author) && !empty($author['photo']) ? $author['photo'] : NULL;

        $user_name = mb_substr($user_name, 0, 58);

        // get existent
        $user = UserHelper::get_by_name($user_name);

        // create
        if (!$user) {
            $user = UserHelper::create($user_name, $user_photo);
        }

        return $user->uid;
    }
}

