<?php
namespace Awful\Models\Traits;

/**
 * For WordPress objects that use `get_metadata()`.
 *
 * User, Post, Term, and Comment.
 */
trait WordPressModelWithMetaTable
{
    /**
     * @param  string $key
     * @return mixed
     */
    final public function getMeta(string $key)
    {
        assert((bool) $key, 'Expected non-empty meta key');

        $value = $this->callMetaFunction('get', $key);
        if (isset($value[0])) {
            // WordPress supports multiple meta values per key.  If
            // multiple exist, we'll let this be an array of each of the
            // values.  But if only one exists, we'll collapse it down
            // so the $key points directly to the single value.
            return isset($value[1]) ? $value : $value[0];
        }
        return $value;
    }

    /**
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    final public function updateMeta(string $key, $value): void
    {
        assert((bool) $key, 'Expected non-empty meta key');

        if ($value === null) {
            $this->callMetaFunction('delete', $key);
        } else {
            $this->callMetaFunction('update', $key, $value);
        }
    }

    /**
     * The `$meta_type` to use to generate the WordPress metadata functions
     * called on this object, as would be passed to `get_metadata()` or is found
     * in the middle of `get_post_meta()`.
     *
     * One of 'user', 'post', 'term', or 'comment'.
     *
     * @return string
     */
    abstract protected function metaType(): string;

    /**
     * @param  string $name
     * @param  mixed  ...$args
     * @return mixed
     */
    private function callMetaFunction(string $name, ...$args)
    {
        $func = "$name\_{$this->metaType()}_meta";
        if (method_exists($this, 'callInSiteContext')) {
            // For objects which use `ModelWithSiteContext`.
            return $this->callInSiteContext($func, $this->id, ...$args);
        }
        // For users.
        return $func($this->id, ...$args);
    }
}
