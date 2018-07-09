<?php
/**
 * Plugin Name: Awful
 * Description: This file bootstraps Awful, a WordPress framework.
 */
use Awful\Awful;
use Awful\Providers\CoreProvider;
use AwfulExample\ExampleProvider;

// Activate the Composer autoloader, which enables referencing all Awful classes
// without further `require` or `include` statements.  If you need to include
// the autoloader earlier in WordPress' bootstrap process (likely in
// `wp-config.php`), you can remove this line.  Otherwise, update the path here
// to match your setup.
require __DIR__ . '/../../../../vendor/autoload.php';

/**
 * This constant short-circuits Awful's initialization to avoid database errors.
 * Set it to `true` once you have added Awful's database tables.
 */
define('AWFUL_INSTALLED', true);

// Awful's bootstrap process will activate your plugins and theme.
Awful::bootstrap([
    // Providers.
    new CoreProvider(),
    new ExampleProvider(),
], [
    // Block types.
]);
