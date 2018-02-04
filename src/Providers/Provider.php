<?php
namespace Awful\Providers;

use Awful\Awful;
use Awful\Container\Container;

/**
 * Providers register custom post types, taxonomies, etc. with Awful, and can
 * provide custom dependency injection bindings.
 */
abstract class Provider
{
    /**
     * The `Awful` instance this provider will register things with.
     *
     * @var Awful
     */
    protected $awful;

    /**
     * The `Container` instance that corresponds with the `Awful` instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * @param Awful     $awful
     * @param Container $container
     */
    final public function __construct(Awful $awful, Container $container)
    {
        $this->awful = $awful;
        $this->container = $container;
    }

    /**
     * Registers classes with the `Awful` instance, and optionally registers
     * bindings with the `Container`.
     *
     * @return void
     */
    abstract public function configure(): void;
}
