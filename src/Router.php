<?php
namespace Awful;

use Awful\Utilities\FilesystemCache;
use FastRoute\RouteCollector;

abstract class Router
{
    private const REDIRECT_PREFIX = '@';

    private const METHOD_PREFIX = '#';

    /** @var FilesystemCache */
    private $cache;

    public function __construct(FilesystemCache $cache)
    {
        $this->cache = $cache;

        $this->dispatcher = \FastRoute\cachedDispatcher([$this, 'initializeDispatcher'], [
            'cacheFile' => $this->getCachePathForCurrentSite(),
            'cacheDisabled' => WP_ENV === 'dev',
        ]);
    }

    final public function handleRequest()
    {
    }

    final public function initializeDispatcher(RouteCollector $r): void
    {
        foreach ($this->getRoutes() as $route => $handler) {
            // Validate the handler.  Should be a formatted string or a list of
            // controller classes.  Fine to leave this validation here because the
            // whole thing is cached
            if ($redirect_to = $this->asRedirectHandler($handler)) {
                if (!$redirect_to) {
                    throw new \Exception("Route '$route' mapped to redirect to nowhere (empty string)!");
                }
            } elseif ($method = $this->asMethodHandler($handler)) {
                if (!is_callable($method)) {
                    throw new \Exception("Route '$route' mapped to non-existent method " . static::class . "::{$method[1]}");
                }
                if (!(new \ReflectionMethod($method[0], $method[1]))->isPublic()) {
                    throw new \Exception("Route '$route' mapped to non-public method " . static::class . "::{$method[1]}");
                }
            } else {
                // It should be an array of controller classes (or a single one)
                $handler = (array) $handler;
                foreach ($handler as $controller_class) {
                    if (!is_string($controller_class)) {
                        throw new \Exception("Route '$route' mapped to a non-string value (" . (is_object($controller_class) ? get_class($controller_class) : gettype($controller_class)) . ')');
                    }

                    $parents = class_parents($controller_class);

                    if (!$parents) {
                        throw new \Exception("Route '$route' mapped to non-existent Controller subclass '$controller_class'");
                    }

                    if (!isset($parents[Controllers\Controller::class])) {
                        throw new \Exception("Route '$route' mapped to a class that is not a \FH\Controllers\Controller subclass ('$controller_class')");
                    }
                }
            }

            // Always needs the preceding slash.
            $route = precedingslashit($route);
            // No reason to get bit by this
            $r->addRoute(self::ALL_METHODS, trailingslashit($route), $handler);
            $r->addRoute(self::ALL_METHODS, untrailingslashit($route), $handler);
        }
    }

    /**
     * Generates a simple route handler that redirects to the given URL when
     * the route is matched.
     *
     * @param  string $to URL to redirect to
     * @return string Implementation detail.
     */
    final protected function redirectHandler(string $to): string
    {
        return self::REDIRECT_PREFIX . $to;
    }

    /**
     * Generates a route handler that calls the given method on the router instance
     * when the route is matched.
     *
     * The method should always return null, or the name of a Controller subclass
     * (or an array of such names).
     *
     * @param  string $method_name The name of the instance method.
     * @return string Implementation detail.
     */
    final protected function methodHandler(string $method_name): string
    {
        return self::METHOD_PREFIX . $method_name;
    }

    /**
     * Extracts the URL to which to redirect from a route handler if applicable.
     *
     * @param  string|mixed $handler
     * @return string|null  The URL if $handler is a redirect handler, else null.
     */
    private function asRedirectHandler($handler): ?string
    {
        if ($handler && is_string($handler) && $handler[0] === self::REDIRECT_PREFIX) {
            return substr($handler, 1);
        }
    }

    /**
     * Extracts a method handler from a route handler if applicable.
     *
     * @param  string|mixed  $handler
     * @return callable|null The method as a callable if $handler is in fact
     *                               a method handler, null otherwise.
     */
    private function asMethodHandler($handler): ?callable
    {
        if ($handler && is_string($handler) && $handler[0] === self::METHOD_PREFIX) {
            return [$this, substr($handler, 1)];
        }
    }
}
