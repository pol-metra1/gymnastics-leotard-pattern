<?php
class GLP_Pattern_Storage {
    private $wpdb;
    private $storage_path;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->storage_path = plugin_dir_path(__DIR__) . 'storage/patterns/';
        if (!is_dir($this->storage_path)) {
            mkdir($this->storage_path, 0755, true);
        }
    }

    public function save_pattern($user_id, $measurements, $pdf_content, $meta) {
        $table = $this->wpdb->prefix . 'gymnastics_patterns';
        $timestamp = current_time('timestamp');
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $rel_dir = "{$year}/{$month}/";
        $full_dir = $this->storage_path . $rel_dir;
        if (!is_dir($full_dir)) mkdir($full_dir, 0755, true);

        $filename = sprintf('pattern_%d_%d_%s.pdf', $user_id, $timestamp, uniqid());
        $filepath = $full_dir . $filename;
        $rel_path = $rel_dir . $filename;
        file_put_contents($filepath, $pdf_content);
        $file_size = filesize($filepath);

        $data = [
            'user_id' => $user_id,
            'measurements' => json_encode($measurements, JSON_UNESCAPED_UNICODE),
            'pattern_format' => $meta['format'],
            'scale_factor' => $meta['scale'],
            'total_pages' => $meta['pages'],
            'pdf_filename' => $filename,
            'pdf_filepath' => $rel_path,
            'file_size' => $file_size,
        ];
        $this->wpdb->insert($table, $data);
        return $this->wpdb->insert_id;
    }

    public function get_pattern_by_id($pattern_id) {
        $table = $this->wpdb->prefix . 'gymnastics_patterns';
        $pattern = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d", $pattern_id
        ), ARRAY_A);
        if ($pattern) {
            $pattern['measurements'] = json_decode($pattern['measurements'], true);
        }
        return $pattern;
    }

    public function get_user_patterns($user_id) {
        $table = $this->wpdb->prefix . 'gymnastics_patterns';
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT id, pattern_format, total_pages, created_at, pdf_filename, file_size 
             FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id
        ), ARRAY_A);
    }

    public function download_pattern($pattern_id) {
        $pattern = $this->get_pattern_by_id($pattern_id);
        if (!$pattern) wp_die('Выкройка не найдена.');
        $full_path = $this->storage_path . $pattern['pdf_filepath'];
        if (!file_exists($full_path)) wp_die('Файл отсутствует.');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pattern['pdf_filename'] . '"');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit;
    }
}
