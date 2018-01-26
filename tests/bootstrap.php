<?php
/**
 * PHPUnit bootstrap file that loads WordPress.
 *
 * @see https://github.com/wp-cli/scaffold-command/blob/master/templates/plugin-bootstrap.mustache
 */

// This is provided by travis.yml or by Varying Vagrant Vagrants.
$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin()
{
    require dirname(__DIR__) . '/vendor/autoload.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load our test base class.
// TODO: Consider enabling autoloading for test classes.
require_once __DIR__ . '/AwfulTestCase.php';
