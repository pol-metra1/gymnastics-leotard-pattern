<?php
/**
 * Template Name: Edit Pattern
 * Шаблон страницы редактирования сохранённой выкройки.
 */

// Проверка авторизации
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Получаем ID выкройки из параметра URL
$pattern_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pattern_id) {
    wp_die(__('Не указан идентификатор выкройки.', 'glp'));
}

// Загружаем данные выкройки
$storage = new GLP_Pattern_Storage();
$pattern = $storage->get_pattern_by_id($pattern_id);

if (!$pattern) {
    wp_die(__('Выкройка не найдена.', 'glp'));
}

// Проверяем права доступа (только владелец)
if ($pattern['user_id'] != get_current_user_id()) {
    wp_die(__('У вас нет прав для редактирования этой выкройки.', 'glp'));
}

// Извлекаем мерки из JSON
$measurements = $pattern['measurements'];

// Получаем данные пользователя (имя и возраст могут быть сохранены отдельно или в мерках)
$user = get_userdata($pattern['user_id']);
$user_name = $user ? $user->display_name : '';
$user_age = isset($measurements['age']) ? $measurements['age'] : '';

get_header();
?>

<div class="glp-container">
    <h1><?php _e('Редактирование выкройки', 'glp'); ?></h1>
    <p><?php printf(__('Редактирование выкройки от %s', 'glp'), date('d.m.Y H:i', strtotime($pattern['created_at']))); ?></p>

    <form id="glp-pattern-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('glp_edit_pattern', '_wpnonce'); ?>
        <input type="hidden" name="action" value="glp_update_pattern">
        <input type="hidden" name="pattern_id" value="<?php echo $pattern_id; ?>">

        <!-- Информация о гимнастке -->
        <fieldset>
            <legend><?php _e('Данные гимнастки', 'glp'); ?></legend>
            <div class="form-row">
                <label><?php _e('Имя', 'glp'); ?> <input type="text" name="name" value="<?php echo esc_attr($user_name); ?>" required></label>
                <label><?php _e('Возраст', 'glp'); ?> <input type="number" name="age" min="3" max="30" value="<?php echo esc_attr($user_age); ?>" required></label>
            </div>
        </fieldset>

        <!-- Основные обхваты -->
        <fieldset>
            <legend><?php _e('Обхваты (см)', 'glp'); ?></legend>
            <div class="form-grid">
                <?php
                $obhvat_fields = ['Og' => 'Обхват груди', 'Ot' => 'Обхват талии', 'Ob' => 'Обхват бёдер', 'Osh' => 'Обхват шеи', 'Or' => 'Обхват руки', 'Ozap' => 'Обхват запястья', 'Onkt' => 'Обхват по нижнему краю трусиков'];
                foreach ($obhvat_fields as $key => $label) {
                    $value = isset($measurements[$key]) ? $measurements[$key] : '';
                    echo '<label>' . esc_html($label) . ' <input type="number" step="0.1" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" required></label>';
                }
                ?>
            </div>
        </fieldset>

        <!-- Длины и высоты -->
        <fieldset>
            <legend><?php _e('Длины (см)', 'glp'); ?></legend>
            <div class="form-grid">
                <?php
                $length_fields = ['Dts' => 'Длина спины до талии', 'Dtp' => 'Длина переда до талии', 'Dp' => 'Длина плеча', 'Dr' => 'Длина рукава', 'Vg' => 'Высота груди', 'DtlsP' => 'Длина от талии до ластовицы спереди', 'DtlsS' => 'Длина от талии до ластовицы сзади', 'Vbt' => 'Высота бока трусиков'];
                foreach ($length_fields as $key => $label) {
                    $value = isset($measurements[$key]) ? $measurements[$key] : '';
                    $required = in_array($key, ['Dts', 'Dtp', 'DtlsP', 'DtlsS', 'Vbt']) ? 'required' : '';
                    echo '<label>' . esc_html($label) . ' <input type="number" step="0.1" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" ' . $required . '></label>';
                }
                ?>
            </div>
        </fieldset>

        <!-- Ширины -->
        <fieldset>
            <legend><?php _e('Ширины (см)', 'glp'); ?></legend>
            <div class="form-grid">
                <?php
                $width_fields = ['Shp' => 'Ширина плеч', 'Shs' => 'Ширина спины', 'Shg' => 'Ширина груди', 'Cg' => 'Центр груди'];
                foreach ($width_fields as $key => $label) {
                    $value = isset($measurements[$key]) ? $measurements[$key] : '';
                    $required = in_array($key, ['Shp', 'Shs', 'Shg']) ? 'required' : '';
                    echo '<label>' . esc_html($label) . ' <input type="number" step="0.1" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" ' . $required . '></label>';
                }
                ?>
            </div>
        </fieldset>

        <!-- Прибавки -->
        <fieldset>
            <legend><?php _e('Прибавки (обычно отрицательные, см)', 'glp'); ?></legend>
            <div class="form-grid">
                <?php
                $increment_fields = ['Pg' => 'Прибавка по груди', 'Pt' => 'Прибавка по талии', 'Pb' => 'Прибавка по бёдрам', 'Pop' => 'Прибавка к обхвату плеча', 'Pshs' => 'Прибавка к ширине спины', 'Pshg' => 'Прибавка к ширине переда', 'Pdts' => 'Прибавка к длине талии спинки'];
                foreach ($increment_fields as $key => $label) {
                    $value = isset($measurements[$key]) ? $measurements[$key] : '';
                    echo '<label>' . esc_html($label) . ' <input type="number" step="0.1" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '"></label>';
                }
                ?>
            </div>
        </fieldset>

        <!-- Опции моделирования -->
        <fieldset>
            <legend><?php _e('Моделирование', 'glp'); ?></legend>
            <div class="form-row">
                <label><?php _e('Тип рукава', 'glp'); ?>
                    <select name="SleeveType">
                        <option value="none" <?php selected(isset($measurements['SleeveType']) ? $measurements['SleeveType'] : 'none', 'none'); ?>><?php _e('Без рукава', 'glp'); ?></option>
                        <option value="set-in" <?php selected(isset($measurements['SleeveType']) ? $measurements['SleeveType'] : '', 'set-in'); ?>><?php _e('Втачной длинный', 'glp'); ?></option>
                        <option value="short" <?php selected(isset($measurements['SleeveType']) ? $measurements['SleeveType'] : '', 'short'); ?>><?php _e('Втачной короткий', 'glp'); ?></option>
                    </select>
                </label>
                <label><?php _e('Юбка', 'glp'); ?> <input type="checkbox" name="Skirt" value="1" <?php checked(!empty($measurements['Skirt']), true); ?>></label>
                <label class="skirt-dependent"><?php _e('Тип юбки', 'glp'); ?>
                    <select name="SkirtType">
                        <option value="straight" <?php selected(isset($measurements['SkirtType']) ? $measurements['SkirtType'] : 'straight', 'straight'); ?>><?php _e('Прямая', 'glp'); ?></option>
                        <option value="half_sun" <?php selected(isset($measurements['SkirtType']) ? $measurements['SkirtType'] : '', 'half_sun'); ?>><?php _e('Полусолнце', 'glp'); ?></option>
                        <option value="sun" <?php selected(isset($measurements['SkirtType']) ? $measurements['SkirtType'] : '', 'sun'); ?>><?php _e('Солнце', 'glp'); ?></option>
                    </select>
                </label>
                <label class="skirt-dependent"><?php _e('Длина юбки (см)', 'glp'); ?> <input type="number" step="0.1" name="SkirtLength" value="<?php echo esc_attr(isset($measurements['SkirtLength']) ? $measurements['SkirtLength'] : '15'); ?>"></label>
            </div>
        </fieldset>

        <button type="submit" class="glp-btn"><?php _e('Сгенерировать новую выкройку', 'glp'); ?></button>
        <a href="<?php echo home_url('/cabinet'); ?>" class="glp-btn" style="background: #6c757d;"><?php _e('Отмена', 'glp'); ?></a>
    </form>
</div>

<script>
    // Предзаполнение условных полей при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        const sleeveSelect = document.querySelector('select[name="SleeveType"]');
        const skirtCheckbox = document.querySelector('input[name="Skirt"]');
        const skirtFields = document.querySelectorAll('.skirt-dependent');
        
        function toggleSkirtFields() {
            const show = skirtCheckbox.checked;
            skirtFields.forEach(field => {
                field.style.display = show ? '' : 'none';
            });
        }
        
        if (skirtCheckbox) {
            skirtCheckbox.addEventListener('change', toggleSkirtFields);
            toggleSkirtFields();
        }
    });
</script>

<?php get_footer(); ?>
