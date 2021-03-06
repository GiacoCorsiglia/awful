<?php
namespace Awful\Models\Traits;

/**
 * For WordPress objects that exist in the context of a specific Site (or
 * "blog") in WordPress multisite.
 *
 * Site, Post, Term, and Comment.
 */
trait WordPressModelWithSiteContext
{
    /**
     * The ID of the Site which owns $this object.
     *
     * @var int
     */
    private $siteId;

    final public function siteId(): int
    {
        return $this->siteId;
    }

    /**
     * Calls the given function in the context of the owner site ID set for this
     * instance, passing along the remaining args and returning the result.
     *
     * @final
     *
     * @param callable $callable Function to invoke.
     * @param mixed ...$args Positional arguments to pass to $callable.
     *
     * @return mixed Return value of invoked function.
     */
    final protected function callInSiteContext(callable $callable, ...$args)
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        assert($this->siteId !== null, 'Expected `$this->siteId` to be set');

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
