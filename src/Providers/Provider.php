<?php
namespace Awful\Providers;

use Awful\Container\Container;

/**
 * Providers register custom post types, taxonomies, etc. with Awful, and can
 * provide custom dependency injection bindings.
 */
abstract class Provider
{
    /**
     * @return string[]
     * @psalm-return array<int, string>
     */
    public function commands(): array
    {
        return [];
    }

    /**
     * @return string[]
     * @psalm-return array<int, string>
     */
    public function plugins(): array
    {
        return [];
    }

    public function register(Container $container): void
    {
    }

    /**
     * @return string[]
     * @psalm-return array<string, class-string>
     */
    public function themes(): array
    {
        return [];
    }
}
