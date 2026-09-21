<?php
/**
 * boot/globals.php is pulled in by composer's "files" autoload and exits unless
 * ABSPATH is defined. PHPUnit's binary loads that autoloader before any bootstrap
 * runs, so the constant has to exist before then — hence auto_prepend_file.
 *
 * Point WP_TESTS_ABSPATH at the WordPress install that bin/install-wp-tests.sh
 * created if it is not in the default temp location.
 */

if (!defined('ABSPATH')) {
    $wpPath = getenv('WP_TESTS_ABSPATH');

    if (!$wpPath) {
        $wpPath = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress/';
    }

    define('ABSPATH', rtrim($wpPath, '/\\') . '/');
}
