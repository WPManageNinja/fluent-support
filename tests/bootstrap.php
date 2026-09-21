<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Fluent_Support
 */

require_once dirname( __DIR__ ) . '/dev/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
$_tests_dir = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/fluent-support.php';
	require dirname( dirname( __FILE__ ) ) . '/../fluent-support-pro/fluent-support-pro.php';

	// Activation normally creates the fs_* tables. Nothing activates the plugin in
	// the test run, and the app queries fs_meta while booting on plugins_loaded,
	// so the schema has to exist before that hook fires.
	\FluentSupport\Database\DBMigrator::run();

	// The Application instance is only handed out through this action, and tests
	// need it to construct framework Request objects.
	add_action( 'fluent_support_loaded', function ( $app ) {
		$GLOBALS['fluent_support_test_app'] = $app;
	} );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// These controllers only ever run inside a REST request. Controller::validate()
// checks REST_REQUEST to decide whether to rethrow a ValidationException or hand it
// to the exception handler, which responds and calls die() with a zero exit status —
// that would end a test run silently green. Defining it keeps failures observable.
if ( ! defined( 'REST_REQUEST' ) ) {
	define( 'REST_REQUEST', true );
}
