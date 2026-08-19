CREATE TABLE IF NOT EXISTS package_features (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_id   BIGINT UNSIGNED NOT NULL,
    feature_id   BIGINT UNSIGNED NOT NULL,
    limit_value  BIGINT UNSIGNED NULL COMMENT 'NULL = unlimited; absence of the row = feature off',
    UNIQUE KEY uq_package_feature (package_id, feature_id),
    CONSTRAINT fk_package_features_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    CONSTRAINT fk_package_features_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
