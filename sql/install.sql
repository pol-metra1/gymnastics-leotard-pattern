CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gymnastics_patterns (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
