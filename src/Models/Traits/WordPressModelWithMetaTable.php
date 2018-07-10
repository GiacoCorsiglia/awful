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
     * @param string $key
     *
     * @return mixed
     */
    final public function getMeta(string $key)
    {
        assert((bool) $key, 'Expected non-empty meta key');

        $value = $this->callMetaFunction('get', $key);
        // WordPress supports multiple meta values per key.
        if (empty($value)) {
            // Either no values exist and `$value === []`, or `$value === false`
            // due to some error.
            return null;
        }
        if (isset($value[0]) && !isset($value[1])) {
            // It's an array of length 1, so just return that single value.
            return $value[0];
        }
        // Return the actual array of multiple values.
        return $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
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
     * @param string $name
     * @param mixed ...$args
     *
     * @return mixed
     */
    private function callMetaFunction(string $name, ...$args)
    {
        $func = "{$name}_{$this->metaType()}_meta";
        if (method_exists($this, 'callInSiteContext')) {
            // For objects which use `WordPressModelWithSiteContext`.
            return $this->callInSiteContext($func, $this->id, ...$args);
        }
        // For users.
        return $func($this->id, ...$args);
    }
}
