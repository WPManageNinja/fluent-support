-- Secondary throwaway database for tiers that drop/recreate tables
-- (wp-browser/WPLoader later). Name MUST contain "test" — the DB guard
-- (tests/lib/guard-production-db.php) refuses anything else.
CREATE DATABASE IF NOT EXISTS fluent_support_loader_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON fluent_support_loader_test.* TO 'fs_lab'@'%';
FLUSH PRIVILEGES;
