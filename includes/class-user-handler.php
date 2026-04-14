<?php
class GLP_User_Handler {
    public static function register_user($username, $email, $password, $display_name = '') {
        if (username_exists($username) || email_exists($email)) {
            return new WP_Error('existing_user', 'Пользователь уже существует.');
        }
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) return $user_id;
        if ($display_name) wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
        return $user_id;
    }
}
