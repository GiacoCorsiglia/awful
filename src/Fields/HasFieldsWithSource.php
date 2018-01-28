<?php
namespace Awful\Fields;

/**
 * Parent class for objects representing ACF sub-fields (repeaters and flexible
 * content fields) whose data lives on a parent object (another HasFields
 * instance).
 */
abstract class HasFieldsWithSource extends HasFields
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
     * $layout           = new HasFieldsWithSource($post, 'flex_1_')
     * $repeater_row     = new HasFieldsWithSource($post, 'flex_1_repeat_0_')
     * ```
     *
     * It's perfectly possible to chain HasFieldsWithSource instances.  However,
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
     * @param HasFields|null      $source   Parent object on which field values are
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

        $this->setFieldsResolver($resolver);

        $this->initialize();
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
     * @return HasFields|null The data source object $this was initialized with, if any.
     */
    final protected function getDataSource()
    {
        return $this->data_source;
    }

    /**
     * @return string The prefix to prepend to field names when requesting them
     *                from the data source object.  May be empty.
     */
    final protected function getDataPrefix(): string
    {
        return $this->data_prefix;
    }

    //////// TODO

    /**
     * @param  string $key Field name as registered in static::getFields()
     * @return string Prefixed field name as saved on data source object.
     */
    private function prefixKey(string $key): string
    {
        return $this->data_prefix . $key;
    }

    final protected function getFromData(string $key, string $as)
    {
        if (isset($this->data[$key])) {
            // Any data that was explicity added via `$this->set()`
            // lives in `$this->data`
            return coerce($this->data[$key], $as);
        }
        if ($this->data_source) {
            return $this->data_source->get($this->prefixKey($key), $as);
        }

        // Might want an empty array or something.
        return coerce(null, $as);
    }

    final protected function getAsRepeater(string $key, string $repeater_class): Repeater
    {
        return new $repeater_class($this->data_source, $this->data_prefix);
    }

    final protected function getAsWrapper(string $key, string $wrapper_class)
    {
        if (!$this->get($key, 'raw')) {
            return null;
        }

        return new $wrapper_class($this->data_source, $this->prefixKey($key) . '_0_');
    }

    final protected function getAsFlexibleContent(string $key, string $flexible_content_class): FlexibleContent
    {
        return new $flexible_content_class($this->data_source, $this->data_prefix);
    }

    final public function update(string $key, $value): HasFields
    {
        assert((bool) $key, 'Expected non-empty $key');

        if ($this->data_source) {
            $this->data_source->update($this->prefixKey($key), $value);
        }

        return $this;
    }

    final public function delete(string $key): HasFields
    {
        assert((bool) $key, 'Expected non-empty $key');

        if ($this->data_source) {
            $this->data_source->delete($this->prefixKey($key));
        }

        return $this;
    }
}
