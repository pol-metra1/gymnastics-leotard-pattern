<?php
/**
 * Plugin Name: Gymnastics Leotard Pattern Builder
 * Description: Построение выкройки купальника для художественной гимнастики с сохранением в PDF и базе данных.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: glp
 */

// Запрет прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определяем константы плагина
define('GLP_VERSION', '1.0.0');
define('GLP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GLP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GLP_STORAGE_PATH', GLP_PLUGIN_PATH . 'storage/patterns/');

// Подключаем необходимые классы
require_once GLP_PLUGIN_PATH . 'includes/class-pattern-calculator.php';
require_once GLP_PLUGIN_PATH . 'includes/class-pdf-generator.php';
require_once GLP_PLUGIN_PATH . 'includes/class-pattern-storage.php';
require_once GLP_PLUGIN_PATH . 'includes/class-user-handler.php';

// ==================== Активация и деактивация ====================
register_activation_hook(__FILE__, 'glp_install');
function glp_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gymnastics_patterns';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        measurements JSON NOT NULL,
        pattern_format VARCHAR(10) NOT NULL DEFAULT 'A1',
        scale_factor FLOAT NOT NULL DEFAULT 1.0,
        total_pages INT NOT NULL,
        pdf_filename VARCHAR(255) NOT NULL,
        pdf_filepath VARCHAR(500) NOT NULL,
        file_size INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
        INDEX idx_user_created (user_id, created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Создаём директорию для хранения PDF, если её нет
    if (!file_exists(GLP_STORAGE_PATH)) {
        mkdir(GLP_STORAGE_PATH, 0755, true);
    }
}

// ==================== Подключение стилей и скриптов ====================
add_action('wp_enqueue_scripts', 'glp_enqueue_assets');
function glp_enqueue_assets() {
    wp_enqueue_style('glp-style', GLP_PLUGIN_URL . 'assets/css/style.css', [], GLP_VERSION);

    // Страница формы создания выкройки
    if (is_page_template('templates/page-new-pattern.php')) {
        wp_enqueue_script('glp-pattern-form', GLP_PLUGIN_URL . 'assets/js/pattern-form.js', ['jquery'], GLP_VERSION, true);
    }

    // Страница личного кабинета
    if (is_page_template('templates/page-cabinet.php')) {
        wp_enqueue_script('glp-cabinet', GLP_PLUGIN_URL . 'assets/js/cabinet.js', ['jquery'], GLP_VERSION, true);
        wp_localize_script('glp-cabinet', 'glp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('glp_cabinet_nonce')
        ]);
    }

    // Страница входа/регистрации
    if (is_page_template('templates/page-login.php')) {
        // При необходимости можно добавить специфичные стили
    }
}

// ==================== Регистрация шаблонов страниц ====================
add_filter('theme_page_templates', 'glp_register_page_templates');
function glp_register_page_templates($templates) {
    $templates['templates/page-new-pattern.php'] = __('Новая выкройка купальника', 'glp');
    $templates['templates/page-cabinet.php']    = __('Мои выкройки', 'glp');
    $templates['templates/page-edit-pattern.php'] = __('Редактирование выкройки', 'glp');
    $templates['templates/page-login.php']      = __('Вход / Регистрация', 'glp');
    return $templates;
}

add_filter('template_include', 'glp_load_page_templates');
function glp_load_page_templates($template) {
    if (is_page()) {
        $page_template = get_page_template_slug();
        if ($page_template && file_exists(GLP_PLUGIN_PATH . $page_template)) {
            return GLP_PLUGIN_PATH . $page_template;
        }
    }
    return $template;
}

// ==================== Шорткоды ====================
add_shortcode('glp_new_pattern', 'glp_shortcode_new_pattern');
function glp_shortcode_new_pattern() {
    ob_start();
    include GLP_PLUGIN_PATH . 'templates/page-new-pattern.php';
    return ob_get_clean();
}

add_shortcode('glp_cabinet', 'glp_shortcode_cabinet');
function glp_shortcode_cabinet() {
    if (!is_user_logged_in()) {
        return '<p>' . __('Пожалуйста, войдите в систему.', 'glp') . '</p>';
    }
    ob_start();
    include GLP_PLUGIN_PATH . 'templates/page-cabinet.php';
    return ob_get_clean();
}

// ==================== Обработчики форм ====================
add_action('admin_post_glp_generate_pattern', 'glp_handle_generate_pattern');
add_action('admin_post_nopriv_glp_generate_pattern', 'glp_handle_generate_pattern');
function glp_handle_generate_pattern() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'glp_save_pattern')) {
        wp_die(__('Ошибка безопасности.', 'glp'));
    }

    $measurements = [];
    $fields = [
        'Og', 'Ot', 'Ob', 'Osh', 'Or', 'Ozap', 'Onkt',
        'Dts', 'Dtp', 'Dp', 'Dr', 'Vg', 'DtlsP', 'DtlsS', 'Vbt',
        'Shp', 'Shs', 'Shg', 'Cg',
        'Pg', 'Pt', 'Pb', 'Pop', 'Pshs', 'Pshg', 'Pdts',
        'Skirt', 'SkirtType', 'SkirtLength', 'SleeveType'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            if (is_numeric($value)) {
                $measurements[$field] = floatval($value);
            } else {
                $measurements[$field] = sanitize_text_field($value);
            }
        } else {
            $measurements[$field] = null;
        }
    }

    $measurements['Skirt'] = isset($_POST['Skirt']) && $_POST['Skirt'] == '1';

    $user_name = sanitize_text_field($_POST['name']);
    $user_age  = intval($_POST['age']);

    $user_id = get_current_user_id();
    if (!$user_id) {
        $password = wp_generate_password();
        $user_login = sanitize_user($user_name . '_' . uniqid());
        $user_email = $user_login . '@temp.leotard';
        $user_id = wp_create_user($user_login, $password, $user_email);
        if (is_wp_error($user_id)) {
            wp_die(__('Не удалось создать пользователя.', 'glp'));
        }
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
    }

    $points = GLP_Pattern_Calculator::calculate_coordinates($measurements);
    $dimensions = GLP_Pattern_Calculator::get_pattern_dimensions($points);
    $pattern_width  = $dimensions['width'];
    $pattern_height = $dimensions['height'];

    $format_info = glp_determine_format($user_age, $measurements['Og']);
    $scale = $format_info['scale'];

    $tile_grid = glp_calculate_tile_grid($pattern_width, $pattern_height, $scale);

    $generator = new GLP_PDF_Generator($points, $pattern_width, $pattern_height, $tile_grid);
    $pdf = $generator->generate();
    $pdf_content = $pdf->Output('S');

    $storage = new GLP_Pattern_Storage();
    $meta = [
        'format' => $format_info['format'],
        'scale'  => $scale,
        'pages'  => $tile_grid['total_pages']
    ];
    $pattern_id = $storage->save_pattern($user_id, $measurements, $pdf_content, $meta);

    wp_redirect(home_url('/cabinet?success=1&pattern_id=' . $pattern_id));
    exit;
}

add_action('admin_post_glp_update_pattern', 'glp_handle_update_pattern');
function glp_handle_update_pattern() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'glp_edit_pattern')) {
        wp_die('Security check');
    }

    $pattern_id = intval($_POST['pattern_id']);
    $storage = new GLP_Pattern_Storage();
    $old_pattern = $storage->get_pattern_by_id($pattern_id);

    if (!$old_pattern || $old_pattern['user_id'] != get_current_user_id()) {
        wp_die('Вы не можете редактировать эту выкройку.');
    }

    $measurements = [];
    $fields = ['Og', 'Ot', 'Ob', 'Osh', 'Or', 'Ozap', 'Onkt', 'Dts', 'Dtp', 'Dp', 'Dr', 'Vg', 'DtlsP', 'DtlsS', 'Vbt', 'Shp', 'Shs', 'Shg', 'Cg', 'Pg', 'Pt', 'Pb', 'Pop', 'Pshs', 'Pshg', 'Pdts', 'Skirt', 'SkirtType', 'SkirtLength', 'SleeveType'];
    foreach ($fields as $f) {
        $measurements[$f] = isset($_POST[$f]) ? (is_numeric($_POST[$f]) ? floatval($_POST[$f]) : sanitize_text_field($_POST[$f])) : null;
    }
    $measurements['Skirt'] = isset($_POST['Skirt']) && $_POST['Skirt'] == '1';

    $user_name = sanitize_text_field($_POST['name']);
    $user_age = intval($_POST['age']);
    $user_id = get_current_user_id();

    $points = GLP_Pattern_Calculator::calculate_coordinates($measurements);
    $dims = GLP_Pattern_Calculator::get_pattern_dimensions($points);
    $width = $dims['width'];
    $height = $dims['height'];

    $formatInfo = glp_determine_format($user_age, $measurements['Og']);
    $scale = $formatInfo['scale'];

    $tileGrid = glp_calculate_tile_grid($width, $height, $scale);

    $generator = new GLP_PDF_Generator($points, $width, $height, $tileGrid);
    $pdf = $generator->generate();
    $pdf_content = $pdf->Output('S');

    $meta = ['format' => $formatInfo['format'], 'scale' => $scale, 'pages' => $tileGrid['total_pages']];
    $new_pattern_id = $storage->save_pattern($user_id, $measurements, $pdf_content, $meta);

    wp_redirect(home_url('/cabinet?updated=1&pattern_id=' . $new_pattern_id));
    exit;
}

add_action('admin_post_glp_download', 'glp_handle_download');
add_action('admin_post_nopriv_glp_download', 'glp_handle_download');
function glp_handle_download() {
    $pattern_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$pattern_id) {
        wp_die(__('Неверный идентификатор выкройки.', 'glp'));
    }
    $storage = new GLP_Pattern_Storage();
    $storage->download_pattern($pattern_id);
}

add_action('admin_post_glp_register_user', 'glp_handle_register_user');
add_action('admin_post_nopriv_glp_register_user', 'glp_handle_register_user');
function glp_handle_register_user() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'glp_register_user')) {
        wp_die(__('Ошибка безопасности.', 'glp'));
    }
    if (!get_option('users_can_register')) {
        wp_redirect(home_url('/login?register=failed&message=' . urlencode(__('Регистрация временно отключена.', 'glp'))));
        exit;
    }

    $username = sanitize_user($_POST['username']);
    $email    = sanitize_email($_POST['email']);
    $display_name = sanitize_text_field($_POST['display_name']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $redirect = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url('/login');

    $errors = [];
    if (empty($username)) $errors[] = __('Имя пользователя обязательно.', 'glp');
    elseif (username_exists($username)) $errors[] = __('Это имя пользователя уже занято.', 'glp');
    if (empty($email) || !is_email($email)) $errors[] = __('Введите корректный email.', 'glp');
    elseif (email_exists($email)) $errors[] = __('Этот email уже используется.', 'glp');
    if (empty($password)) $errors[] = __('Пароль обязателен.', 'glp');
    elseif (strlen($password) < 6) $errors[] = __('Пароль должен содержать минимум 6 символов.', 'glp');
    if ($password !== $password_confirm) $errors[] = __('Пароли не совпадают.', 'glp');

    if (!empty($errors)) {
        wp_redirect(add_query_arg(['register' => 'failed', 'message' => urlencode(implode(' ', $errors))], home_url('/login')));
        exit;
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_redirect(add_query_arg(['register' => 'failed', 'message' => urlencode($user_id->get_error_message())], home_url('/login')));
        exit;
    }

    wp_update_user(['ID' => $user_id, 'display_name' => $display_name ?: $username]);
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    wp_new_user_notification($user_id, null, 'both');

    wp_redirect($redirect);
    exit;
}

add_action('wp_ajax_glp_delete_pattern', 'glp_ajax_delete_pattern');
function glp_ajax_delete_pattern() {
    if (!wp_verify_nonce($_POST['nonce'], 'glp_cabinet_nonce')) {
        wp_send_json_error(__('Ошибка безопасности.', 'glp'));
    }
    $pattern_id = intval($_POST['pattern_id']);
    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error(__('Необходимо авторизоваться.', 'glp'));
    $storage = new GLP_Pattern_Storage();
    $pattern = $storage->get_pattern_by_id($pattern_id);
    if (!$pattern || $pattern['user_id'] != $user_id) wp_send_json_error(__('Выкройка не найдена или у вас нет прав.', 'glp'));
    $deleted = $storage->delete_pattern($pattern_id);
    $deleted ? wp_send_json_success() : wp_send_json_error(__('Не удалось удалить выкройку.', 'glp'));
}

// ==================== Вспомогательные функции ====================
function glp_determine_format($age, $chest) {
    if ($age < 7) return ['format' => 'A3', 'scale' => 1.0];
    if ($age < 11) return ['format' => 'A2', 'scale' => 1.0];
    if ($chest > 95) return ['format' => 'A1', 'scale' => 0.95];
    return ['format' => 'A1', 'scale' => 1.0];
}

function glp_calculate_tile_grid($width_mm, $height_mm, $scale = 1.0) {
    $page_width  = 210;
    $page_height = 297;
    $margin = 10;
    $usable_width  = $page_width - 2 * $margin;
    $usable_height = $page_height - 2 * $margin;
    $scaled_width  = $width_mm * $scale;
    $scaled_height = $height_mm * $scale;
    $cols = max(1, ceil($scaled_width / $usable_width));
    $rows = max(1, ceil($scaled_height / $usable_height));
    $tile_width  = $scaled_width / $cols;
    $tile_height = $scaled_height / $rows;
    if ($tile_width > $usable_width) { $cols = ceil($scaled_width / $usable_width); $tile_width = $scaled_width / $cols; }
    if ($tile_height > $usable_height) { $rows = ceil($scaled_height / $usable_height); $tile_height = $scaled_height / $rows; }
    return [
        'cols' => $cols, 'rows' => $rows, 'total_pages' => $cols*$rows,
        'tile_width' => $tile_width, 'tile_height' => $tile_height,
        'scaled_width' => $scaled_width, 'scaled_height' => $scaled_height,
        'margin' => $margin
    ];
}
