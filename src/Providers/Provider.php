<?php
namespace Awful\Providers;

use Awful\Container\Container;

/**
 * Providers register custom post types, taxonomies, etc. with Awful, and can
 * provide custom dependency injection bindings.
 */
abstract class Provider
{
    public function register(Container $container): void
    {
    }

    public function plugins(): array
    {
        return [];
    }

    public function commands(): array
    {
        return [];
    }

    public function themes(): array
    {
        return [];
    }
}
