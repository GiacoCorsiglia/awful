<?php
/**
 * Plugin Name: Awful
 * Description: This file bootstraps Awful, a WordPress framework.
 */
use Awful\Awful;
use Awful\Providers\CoreProvider;

/**
 * This constant short-circuits Awful's initialization to avoid database errors.
 * Set it to `true` once you have added Awful's database tables.
 */
define('AWFUL_INSTALLED', false);
Awful::bootstrap([
    // Providers.
    new CoreProvider(),
], [
    // Block types.
]);
