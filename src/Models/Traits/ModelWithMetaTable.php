<?php
namespace Awful\Models\Traits;

/**
 * Undocumented trait.
 */
trait ModelWithMetaTable
{
    protected function fetchData(): void
    {
        if (!$this->exists()) {
            return;
        }

        /** @var callable */
        $get_meta = 'get_' . $this->getMetaType() . '_meta';

        if (method_exists($this, 'callInSiteContext')) {
            // For posts and taxonomy terms which use `ModelWithSiteContext`.
            /** @var array */
            $metadata = $this->callInSiteContext($get_meta, $this->id);
        } else {
            // For users.
            /** @var array */
            $metadata = $get_meta($this->id);
        }

        foreach ($metadata as $key => $value) {
            if (isset($value[0])) {
                // WordPress supports multiple meta values per key.  If
                // multiple exist, we'll let this be an array of each of the
                // values.  But if only one exists, we'll collapse it down
                // so the $key points directly to the single value.
                $this->data[$key] = isset($value[1]) ? $value : $value[0];
            }
        }
    }

    /**
     * The `$meta_type` to use to generate the WordPress metadata functions
     * called on this object, as would be passed to `get_metadata()` or is found
     * in the middle of `get_post_meta()`.
     *
     * One of 'user', 'post', or 'term'.
     *
     * @return string
     */
    abstract protected function getMetaType(): string;
}
