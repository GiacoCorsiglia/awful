<?php
namespace Awful\Templates\Twig\Extensions;

use Closure;
use Twig_Extension;
use Twig_Function;

/**
 * A Twig extension that exposes a handful of WordPress functionality.
 */
class General extends Twig_Extension
{
    public function getFunctions(): array
    {
        return [
            new Twig_Function('do_action', $this->wrap('do_action')),
            new Twig_Function('get_body_class', 'get_body_class'),
            new Twig_Function('wp_footer', $this->wrap('wp_footer')),
            new Twig_Function('wp_title', $this->wrap('wp_title')),
            new Twig_Function('wp_head', $this->wrap('wp_head')),
        ];
    }

    /**
     * Wraps a WordPress function to capture any output and return it instead.
     *
     * @param callable $callable Function to wrap.
     *
     * @return Closure Wrapped version.
     */
    private function wrap(callable $callable): Closure
    {
        return function () use ($callable): string {
            ob_start();
            call_user_func_array($callable, func_get_args());
            $output = ob_get_contents();
            ob_end_clean();
            return (string) $output;
        };
    }
}
