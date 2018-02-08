<?php
namespace Awful\Models\Traits;

trait ModelWithSiteContext
{
    /**
     * The ID of the Site which owns.
     *
     * @var int
     */
    protected $site_id;

    /**
     * Calls the given function in the context of the owner site ID set for this
     * instance, passing along the remaining args and returning the result.
     *
     * @final
     *
     * @param callable $callable Function to invoke.
     * @param mixed    ...$args  Positional arguments to pass to $callable.
     *
     * @return mixed Return value of invoked function.
     */
    final protected function callInSiteContext(callable $callable, ...$args)
    {
        assert(!is_null($this->site_id), 'Expected `$this->site_id` to be set');

        if ($this->site_id) {
            switch_to_blog($this->site_id);
        }

        $ret = $callable(...$args);

        if ($this->site_id) {
            restore_current_blog();
        }

        return $ret;
    }
}
