<?php
/**
 * Plugin Name: Awful
 * Description: This file bootstraps Awful, a WordPress framework.
 */
use Awful\Awful;
use Awful\Providers\CoreProvider;

Awful::bootstrap([
    new CoreProvider(),
]);
