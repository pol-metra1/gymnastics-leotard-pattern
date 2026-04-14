<?php
/**
 * Удаление плагина Gymnastics Leotard Pattern Builder
 *
 * Этот файл выполняется при удалении плагина через админку WordPress.
 * Удаляет таблицы, созданные плагином, и очищает следы.
 *
 * @package GLP
 */

// Запрет прямого вызова

if (!defined('WP_UNINSTALL_PLUGIN')) exit;
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gymnastics_patterns");
