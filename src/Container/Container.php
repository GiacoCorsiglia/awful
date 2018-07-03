<?php
namespace Awful\Container;

use Awful\Container\Exceptions\AlreadyRegisteredException;
use Awful\Container\Exceptions\CircularDependencyException;
use Awful\Container\Exceptions\ClassDoesNotExistException;
use Awful\Container\Exceptions\NotFoundException;
use Awful\Container\Exceptions\UninstantiatableClassException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Dependency injection container which manages singletons.
 */
final class Container implements ContainerInterface
{
    /**
     * Dictionary of aliases that maps alias to class.
     *
     * @var string[]
     */
    private $aliases = [];

    /**
     * Stack of classnames that are resolving to track circular dependencies.
     * Classnames are stored as the array keys for faster lookups.
     *
     * @var bool[]
     */
    private $currently_resolving = [];

    /**
     * Stash of already resolved instances.
     *
     * @var object[]
     */
    private $instances = [];

    public function __construct()
    {
        // Allow injection of the container itself.
        $this->instances[self::class] = $this;
        $this->aliases[ContainerInterface::class] = self::class;
    }

    /**
     * Associates the list of $aliases with the given $class, meaning an
     * instance of $class will be returned whenever any of the $aliases is
     * requested.
     *
     * Though no warnings will be issued, the only proper use of aliasing is to
     * tie a particular subclass to a parent class or interface.
     *
     * @param string $class Name of the class to actually instantiate.
     * @param string ...$aliases One or more alternative class/interface names.
     *
     * @return self $this
     */
    public function alias(string $class, string ...$aliases): self
    {
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $class;
        }
        return $this;
    }

    /**
     * Calls the given function or method, resolving and passing any
     * dependencies indicated by parameter type hints.
     *
     * Dependency resolution stops with the first positional parameter that
     * either does not specify a type or specifies a scalar type.
     *
     * @param callable $callable Function or method to call.
     * @param mixed ...$args Additional arguments to pass to the callable.
     *
     * @return mixed Whatever value is returned by the callable.
     *
     * @psalm-suppress PossiblyInvalidArgument see `new ReflectionFunction()`.
     * @psalm-suppress InvalidArgument see `new ReflectionFunction()`.
     */
    public function call(callable $callable, ...$args)
    {
        if (is_string($callable) && strpos($callable, '::')) {
            $callable = explode('::', $callable);
        }
        $reflection = is_array($callable)
            ? new ReflectionMethod($callable[0], $callable[1])
            : new ReflectionFunction($callable);
        $dependencies = $this->resolveParameters($reflection);
        return call_user_func($callable, ...$dependencies, ...$args);
    }

    /**
     * @param string $id
     *
     * @return object Entry.
     */
    public function get($id)
    {
        $class = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        try {
            $this->instances[$class] = $this->instantiate($class);
        } catch (ClassDoesNotExistException $e) {
            throw new NotFoundException($id, 0, $e);
        }

        return $this->instances[$class];
    }

    public function has($id): bool
    {
        return class_exists($aliases[$id] ?? $id);
    }

    /**
     * Instantiates the given class, resolving and passing any dependencies
     * required by the constructor (indicated by parameter type hints).
     *
     * This method will happily instantiate the same class multiple times: it
     * does not attempt to enforce singletons.  For that, use Container::get.
     * Dependency resolution stops with the first positional parameter that
     * either does not specify a type or specifies a scalar type.
     *
     * @param string $class Class to instantiate.
     * @param mixed ...$args Additional arguments to pass to the constructor.
     *
     * @throws ClassDoesNotExistException
     * @throws UninstantiatableClassException
     *
     * @return object Instance of the given $class.
     */
    public function instantiate(string $class, ...$args)
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ClassDoesNotExistException($class, 0, $e);
        }

        if ($constructor = $reflection->getConstructor()) {
            // For some reason Psalm doesn't like this.
            /** @psalm-suppress RedundantCondition */
            if (!$constructor->isPublic()) {
                throw new UninstantiatableClassException($class, 0);
            }

            $this->currently_resolving[$class] = true;
            $dependencies = $this->resolveParameters($constructor);
            unset($this->currently_resolving[$class]);

            $instance = new $class(...$dependencies, ...$args);
        } else {
            $instance = new $class();
        }

        /** @psalm-suppress UndefinedClass */
        if (in_array(ChainedDependencies::class, $reflection->getTraitNames())) {
            $instance->injectDependencies($this->resolveChainedDependencies($class));
        }

        return $instance;
    }

    /**
     * Explicitly registers an instance in the container.
     *
     * The $instance will be registered under its own class name.
     *
     * @param object $instance Instance to register.
     * @param string ...$aliases Optional list of aliases for the $instance.
     *
     * @throws AlreadyRegisteredException
     *
     * @return self $this
     */
    public function register(object $instance, string ...$aliases): self
    {
        $class = get_class($instance);

        if (isset($this->instances[$class])) {
            throw new AlreadyRegisteredException($class);
        }

        $this->instances[$class] = $instance;
        $this->alias($class, ...$aliases);

        return $this;
    }

    /**
     * Recursively resolves dependencies specified by the passed class'
     * DEPENDENCIES constant, and the DEPENDENCIES specified by its parents.
     *
     * Overridden dependencies in child classes are disregarded, as those could
     * potentially break something in a parent class!
     *
     * @param string $subclass Name of the class seeking chained dependencies.
     *
     * @return object[] Array of resolved dependencies keyed by property name.
     */
    private function resolveChainedDependencies(string $subclass): array
    {
        $chain = class_parents($subclass);
        $chain[] = $subclass;
        $dependencies = [];
        foreach ($chain as $class) {
            if (defined("$class::DEPENDENCIES")) {
                $dependencies += $class::DEPENDENCIES;
            }
        }
        $resolved = [];
        foreach ($dependencies as $key => $id) {
            $resolved[$key] = $this->get($id);
        }
        return $resolved;
    }

    /**
     * Recursively resolves dependencies indicated by the passed function's
     * parameter type hints.
     *
     * Resolution stops with the first positional parameter that either does not
     * specify a type or specifies a scalar type.
     *
     * @param ReflectionFunctionAbstract $func ReflectionMethod or Function.
     *
     * @throws CircularDependencyException
     *
     * @return object[] List of resolved dependencies.
     */
    private function resolveParameters(ReflectionFunctionAbstract $func): array
    {
        $resolved = [];
        $parameters = $func->getParameters();
        foreach ($parameters as $parameter) {
            $class = $parameter->getClass();
            if (!$class) {
                break;
            }
            $class = $class->getName();
            if (isset($this->currently_resolving[$class])) {
                throw new CircularDependencyException($class);
            }
            $resolved[] = $this->get($class);
        }
        return $resolved;
    }
}
