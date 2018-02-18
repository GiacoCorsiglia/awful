<?php
namespace Awful\Models;

use Awful\Models\Fields\FieldsResolver;

/**
 * Parent class for objects representing ACF sub-fields (repeaters and flexible
 * content fields) whose data lives on a parent object (a `Model` instance).
 */
abstract class SubModel extends HasFields
{
    /** @var HasFields|null */
    private $data_source;

    /** @var string */
    private $data_prefix;

    /**
     * Construct new instance whose field values are saved on the $source object.
     *
     * Consider a repeater field named 'repeat' inside a flexible content field named
     * 'flex' all saved on a post.  Here's what the chain would look like for the
     * first row in the repeater field saved on the second layout in the flexible
     * content field:
     *
     * ```php
     * $post             = new HasFields()
     * $layout           = new SubModel($post, 'flex_1_')
     * $repeater_row     = new SubModel($post, 'flex_1_repeat_0_')
     * ```
     *
     * It's perfectly possible to chain SubModel instances.  However,
     * since the chain must always end at the object who actually has the fields
     * saved on it (HasFieldsInDatabase instance), we skip the
     * middle men and thread the same data source through to child field instances,
     * building up the prefix as we go.
     *
     * Here's essentially what happens when you run `$repeater_row->get('foo')`:
     *
     * ```php
     * return $post->get('flex_1_repeat_0_foo')
     * ```
     *
     * It's also reasonable to create an instance without a data source.  In that
     * case field values can be added using `$this->set()`.  Useful for creating
     * default/fallback instances when non is saved in the database.
     * NOTE: `$this->update()` and `$this->delete()` will do nothing if there's
     * no data source.
     *
     * @param Model|null          $source   Parent object on which field values are
     *                                      saved, if any.
     * @param string              $prefix   Prefix for field names as they are saved on $source, if any.
     * @param null|FieldsResolver $resolver Resolver to use when
     */
    final public function __construct(
        HasFields $source = null,
        string $prefix = '',
        FieldsResolver $resolver = null
    ) {
        $this->data_source = $source;
        $this->data_prefix = $prefix;

        $this->initializeFieldsResolver($resolver);

        $this->initialize();
    }

    /**
     * @return HasFields|null The data source object $this was initialized with, if any.
     */
    final public function getDataSource(): ?HasFields
    {
        return $this->data_source;
    }

    /**
     * @return string The prefix to prepend to field names when requesting them
     *                from the data source object.  May be empty.
     */
    final public function getDataPrefix(): string
    {
        return $this->data_prefix;
    }

    /**
     * Hook to allow sub classes to run code on `__construct()` without having
     * to re-declare the constructor.
     *
     * @return void
     */
    protected function initialize()
    {
    }

    /**
     * @param  string $key Field name as registered in static::getFields()
     * @return string Prefixed field name as saved on data source object.
     */
    private function prefixKey(string $key): string
    {
        return $this->data_prefix . $key;
    }

    final public function getRawFieldValue(string $key)
    {
        if (isset($this->data[$key])) {
            // Any data that was explicity added via `$this->set()`
            // lives in `$this->data`
            return $this->data[$key];
        }
        if ($this->data_source) {
            return $this->data_source->getRawFieldValue($this->prefixKey($key));
        }

        return null;
    }
}
