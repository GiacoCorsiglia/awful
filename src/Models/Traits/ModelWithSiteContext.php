<?php
namespace Awful\Models\Traits;

trait ModelWithSiteContext
{
    /**
     * The ID of the Site which owns $this object.
     *
     * @var int
     */
    private $siteId;

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
        assert(!is_null($this->siteId), 'Expected `$this->siteId` to be set');

        $switched = $this->siteId && get_current_blog_id() !== $this->siteId;
        if ($switched) {
            switch_to_blog($this->siteId);
        }

        $ret = $callable(...$args);

        if ($switched) {
            restore_current_blog();
        }

        return $ret;
    }
}
