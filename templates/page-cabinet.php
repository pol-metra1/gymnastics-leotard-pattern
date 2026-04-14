<?php
/* Template Name: My Patterns Cabinet */
get_header();
if (!is_user_logged_in()) {
    echo '<p>Пожалуйста, <a href="'.wp_login_url().'">войдите</a>.</p>';
    get_footer();
    return;
}
$storage = new GLP_Pattern_Storage();
$patterns = $storage->get_user_patterns(get_current_user_id());
?>
<div class="glp-container">
    <h1>Мои выкройки</h1>
    <?php if (empty($patterns)): ?>
        <p>У вас пока нет сохранённых выкроек. <a href="<?php echo home_url('/new-pattern'); ?>">Создать первую</a>.</p>
    <?php else: ?>
        <table class="glp-table">
            <thead><tr><th>Дата</th><th>Формат</th><th>Страниц</th><th>Размер</th><th>Действия</th></tr></thead>
            <tbody>
            <?php foreach ($patterns as $p): ?>
                <tr>
                    <td><?php echo date('d.m.Y H:i', strtotime($p['created_at'])); ?></td>
                    <td><?php echo esc_html($p['pattern_format']); ?></td>
                    <td><?php echo $p['total_pages']; ?></td>
                    <td><?php echo size_format($p['file_size']); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin-post.php?action=glp_download&id='.$p['id']); ?>" class="glp-btn small">Скачать</a>
                        <a href="<?php echo home_url('/edit-pattern?id='.$p['id']); ?>" class="glp-btn small">Редактировать</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
