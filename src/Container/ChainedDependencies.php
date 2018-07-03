<?php
namespace Awful\Container;

/**
 * Indicates to the Container that the using class specifies dependencies in a
 * `DEPENDENCIES` constant and provides a method for injecting them.
 *
 * Subclasses can specify additional dependencies in their own `DEPENDENCIES`
 * constant without re-specifying dependencies of parent classes as the
 * Container will resolve all of them for the whole inheritance chain.  However,
 * child classes cannot change or remove dependency keys of parent classes.
 *
 * Simple example:
 * ```
 * class Hook
 * {
 *     use \Awful\Container\ChainedDependencies;
 *
 *     const DEPENDENCIES = [
 *         'theme' => \Awful\Theme::class,
 *     ];
 *
 *     private $theme;
 *
 *     public function getThemeName(): string
 *     {
 *         return $this->theme->getName();
 *     }
 * }
 * ```
 */
trait ChainedDependencies
{
    /**
     * Stashes the injected dependencies on the object; primarily for use by
     * the Container.
     *
     * @see \Awful\Container\Container::instantiate()
     *
     * @internal
     *
     * @param object[] $dependencies Resolved instances keyed by property name.
     *
     * @return void
     */
    public function injectDependencies(array $dependencies): void
    {
        foreach ($dependencies as $key => $instance) {
            $this->$key = $instance;
        }
    }
}
