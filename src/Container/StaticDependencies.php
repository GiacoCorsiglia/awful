<?php
namespace Awful\Container;

use Awful\Container\Exceptions\UnresolvedStaticDependencyException;

/**
 * Used to inject bootstrap dependencies into the static layer of a class.
 *
 * Due to the nature of static properties, this must only be used on base class
 * in inheritance chain (or at least the top)
 *
 * @internal This is used to facilitate the bootstrapping encoded in the static
 * layer of HasFields classes.  Normal (instance-level) dependency injection
 * should be preferred elsewhere.
 */
trait StaticDependencies
{
    /**
     * Map of subclass name to maps of resolved dependencies.
     *
     * Due to the nature of static properties, re-declaring this property on a
     * child class can break the injection.
     *
     * @internal Used by Awful's dependency injection container.
     * @var (object[])[]
     */
    public static $_static_dependencies = [];

    /**
     * Returns the already-injected dependency; does not resolve dependencies.
     *
     * This method assumes Container::injectStatic has been run on this class.
     *
     * @param  string                              $id Dependency ID, as in the container
     * @throws UnresolvedStaticDependencyException
     * @return object                              Dependency instance.
     */
    protected static function getStaticDependency(string $id)
    {
        if (isset(self::$_static_dependencies[static::class][$id])) {
            return self::$_static_dependencies[static::class][$id];
        }
        throw new UnresolvedStaticDependencyException(static::class, $id);
    }
}
