<?php
/**
 * Template Name: Login / Register
 * Шаблон страницы авторизации и регистрации.
 */

// Если пользователь уже авторизован, перенаправляем в личный кабинет
if (is_user_logged_in()) {
    wp_redirect(home_url('/cabinet'));
    exit;
}

get_header();
?>

<div class="glp-container glp-auth-container">
    <div class="glp-auth-tabs">
        <button class="glp-tab-btn active" data-tab="login"><?php _e('Вход', 'glp'); ?></button>
        <?php if (get_option('users_can_register')) : ?>
            <button class="glp-tab-btn" data-tab="register"><?php _e('Регистрация', 'glp'); ?></button>
        <?php endif; ?>
    </div>

    <div class="glp-auth-content">
        <!-- Форма входа -->
        <div id="glp-login-form" class="glp-auth-form active">
            <h2><?php _e('Вход в личный кабинет', 'glp'); ?></h2>

            <?php
            // Выводим сообщения об ошибках или успешной регистрации
            if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                echo '<div class="glp-message glp-message-error">' . __('Неверное имя пользователя или пароль.', 'glp') . '</div>';
            }
            if (isset($_GET['registered']) && $_GET['registered'] == 'success') {
                echo '<div class="glp-message glp-message-success">' . __('Регистрация прошла успешно! Теперь вы можете войти.', 'glp') . '</div>';
            }
            if (isset($_GET['password']) && $_GET['password'] == 'reset') {
                echo '<div class="glp-message glp-message-success">' . __('Инструкции по сбросу пароля отправлены на вашу почту.', 'glp') . '</div>';
            }
            ?>

            <?php
            // Аргументы для wp_login_form
            $args = array(
                'redirect'       => home_url('/cabinet'),
                'form_id'        => 'glp-loginform',
                'label_username' => __('Имя пользователя или Email', 'glp'),
                'label_password' => __('Пароль', 'glp'),
                'label_remember' => __('Запомнить меня', 'glp'),
                'label_log_in'   => __('Войти', 'glp'),
                'remember'       => true,
            );
            wp_login_form($args);
            ?>

            <div class="glp-form-footer">
                <a href="<?php echo wp_lostpassword_url(get_permalink()); ?>"><?php _e('Забыли пароль?', 'glp'); ?></a>
            </div>
        </div>

        <!-- Форма регистрации (только если разрешена) -->
        <?php if (get_option('users_can_register')) : ?>
            <div id="glp-register-form" class="glp-auth-form">
                <h2><?php _e('Регистрация', 'glp'); ?></h2>

                <?php
                if (isset($_GET['register']) && $_GET['register'] == 'failed') {
                    $error_message = isset($_GET['message']) ? urldecode($_GET['message']) : __('Ошибка регистрации. Пожалуйста, проверьте данные.', 'glp');
                    echo '<div class="glp-message glp-message-error">' . esc_html($error_message) . '</div>';
                }
                ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('glp_register_user', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="glp_register_user">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/login?registered=success')); ?>">

                    <p class="glp-form-field">
                        <label for="reg_username"><?php _e('Имя пользователя', 'glp'); ?> <span class="required">*</span></label>
                        <input type="text" name="username" id="reg_username" required>
                    </p>

                    <p class="glp-form-field">
                        <label for="reg_email"><?php _e('Email', 'glp'); ?> <span class="required">*</span></label>
                        <input type="email" name="email" id="reg_email" required>
                    </p>

                    <p class="glp-form-field">
                        <label for="reg_display_name"><?php _e('Отображаемое имя (как к вам обращаться)', 'glp'); ?></label>
                        <input type="text" name="display_name" id="reg_display_name">
                    </p>

                    <p class="glp-form-field">
                        <label for="reg_password"><?php _e('Пароль', 'glp'); ?> <span class="required">*</span></label>
                        <input type="password" name="password" id="reg_password" required minlength="6">
                        <span class="glp-field-hint"><?php _e('Минимум 6 символов', 'glp'); ?></span>
                    </p>

                    <p class="glp-form-field">
                        <label for="reg_password_confirm"><?php _e('Подтверждение пароля', 'glp'); ?> <span class="required">*</span></label>
                        <input type="password" name="password_confirm" id="reg_password_confirm" required>
                    </p>

                    <p class="glp-form-submit">
                        <button type="submit" class="glp-btn"><?php _e('Зарегистрироваться', 'glp'); ?></button>
                    </p>
                </form>

                <div class="glp-form-footer">
                    <p><?php _e('Регистрируясь, вы соглашаетесь с правилами использования сервиса.', 'glp'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Простые стили для табов и форм (можно добавить в общий CSS) */
    .glp-auth-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        border-bottom: 1px solid #ddd;
    }
    .glp-tab-btn {
        background: none;
        border: none;
        padding: 12px 25px;
        font-size: 16px;
        font-weight: bold;
        color: #666;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }
    .glp-tab-btn.active {
        color: #e91e63;
        border-bottom-color: #e91e63;
    }
    .glp-auth-form {
        display: none;
    }
    .glp-auth-form.active {
        display: block;
    }
    .glp-form-field {
        margin-bottom: 20px;
    }
    .glp-form-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .glp-form-field input {
        width: 100%;
        max-width: 400px;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }
    .glp-field-hint {
        display: block;
        font-size: 12px;
        color: #777;
        margin-top: 4px;
    }
    .required {
        color: #e91e63;
    }
    .glp-message {
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    .glp-message-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .glp-message-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .glp-form-footer {
        margin-top: 20px;
        font-size: 14px;
    }
</style>

<script>
    (function() {
        const tabs = document.querySelectorAll('.glp-tab-btn');
        const forms = document.querySelectorAll('.glp-auth-form');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.dataset.tab;
                tabs.forEach(t => t.classList.remove('active'));
                forms.forEach(f => f.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('glp-' + target + '-form').classList.add('active');
            });
        });
    })();
</script>

<?php get_footer(); ?>
