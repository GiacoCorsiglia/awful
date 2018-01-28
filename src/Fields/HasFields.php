<?php
namespace Awful\Fields;

use Awful\Container\Container;
use Awful\Models\Model;

/**
 * Base class for any object with that can have Fields.
 */
abstract class HasFields
{
    /**
     * The `FieldsResolver` to use for resolving field definitions on instances
     * if one is not set explicitly in the constructor.
     *
     * Having a default makes normal model instantiation much more convenient,
     * as it likely corresponds with the current site, and normally only one
     * site is relevant per request.
     *
     * @var FieldsResolver
     */
    private static $default_fields_resolver;

    /**
     * Sets the `FieldsResolver` instance to use when resolving field
     * definitions if none is set explicitly upon instance creation.
     *
     * @param FieldsResolver $resolver
     *
     * @return void
     *
     * @internal Exposed for use internally by Awful, or for explicit testing.
     */
    final public static function setDefaultFieldsResolver(FieldsResolver $resolver): void
    {
        self::$default_fields_resolver = $resolver;
    }

    /**
     * Enumerates any custom fields that can be saved on this object.
     *
     * Enumeration excludes any built-in model fields (i.e., database columns).
     *
     * If a callable is returned, it will be invoked via the dependency
     * injection container, and is expected to return an array itself.
     *
     * @return array|callable Either an array of fields, or a function that
     *                        resolves to an array of fields.
     */
    public static function getFields()
    {
        return [];
    }

    /** @var mixed[] Cached field values on this instance */
    protected $data = [];

    /** @var FieldsResolver */
    private $fields_resolver;

    /** @var mixed[] */
    private $filtered_data_cache = [];

    /**
     * Fetches the value of the field saved on this object, and tries to cast it
     * to the correct format for its corresponding field definition (if any).
     *
     * Pass `true` for `$raw` to avoid an infinite loop if called within a
     * class' own `getFields()` method.
     *
     * @param string $key The name of the field; i.e., its key in the array
     *                    returned by getFields()
     * @param bool   $raw Pass `true` to receive the value exactly as it is saved
     *                    in the database (unserialized) rather than cast to the
     *                    correct type for the corresponding field.
     *
     * @return mixed The value of the field, or null if unset.
     */
    final public function get(string $key, bool $raw = false)
    {
        assert((bool) $key, 'Expected non-empty $key');

        if (!$raw && isset($this->filtered_data_cache[$key])) {
            return $this->filtered_data_cache[$key];
        }

        $value = $this->getRaw($key);

        if ($raw) {
            return $value;
        }

        if ($field = static::getField($key)) {
            return $this->filtered_data_cache[$key] = $field->forPhp($value, $this, $key);
        }

        return $this->filtered_data_cache[$key] = $value;
    }

    /**
     * Fetches the value from the database without any filtering other than
     * unserialization.
     *
     * @param string $key The name of the field.
     *
     * @return mixed The raw value of the field, or null if unset.
     */
    abstract protected function getRaw(string $key);

    /**
     * Allows manually setting a value on this instance for its lifetime; the
     * value set will not be saved in the database.
     *
     * @param mixed[] $data  The name of the field to set, or an
     *                       associative array of field => value
     *                       pairs.
     * @param mixed   $value The value to assign.
     *
     * @return $this
     */
    final public function set(array $data): self
    {
        foreach ($data as $key => $value) {
            assert(is_string($key), 'Expected associative array');

            if (isset($this->filtered_data_cache[$key])) {
                unset($this->filtered_data_cache[$key]);
            }

            // TODO
            if ($value instanceof Model) {
                $this->filtered_data_cache[$key] = $value;
                $this->data[$key] = $value->getId();
            } elseif ($value instanceof HasFieldsWithSource) {
                // Some recursive shit
            }
            // OR
            if ($field = static::getField($key)) {
                $this->data = $field->toDatabaseData($key) + $this->data;
            }
        }

        return $this;
    }

    /**
     * Sets the value saved on this object in the database for this key.
     *
     * In WordPress, that corresponds with an UPDATE or INSERT on a meta table,
     * or an UPDATE on a model table (posts, etc).
     *
     * @param string $key   Name of the custom field or database column.
     * @param mixed  $value The value to set.  Must be Serializable.
     * @param array  $data
     *
     * @return $this
     */
    abstract public function update(array $data): self;

    /**
     * Deletes the value saved on this object in the database for this key.
     *
     * In WordPress, that means actually deleting a row in a meta table, or
     * unsetting a column in a model table (likely inadvisable).
     *
     * @param string ...$keys Names of one or more fields to unset.
     *
     * @return $this
     */
    abstract public function delete(string ...$keys): self;

    /**
     * Sets the FieldsResolver instance to use when resolving field definitions
     * for this instance.
     *
     * @param FieldsResolver|null $resolver If null, the default will be used.
     *
     * @return $this
     *
     * @internal Exposed for use internally by Awful, or for explicit testing.
     */
    final protected function setFieldsResolver(FieldsResolver $resolver = null): self
    {
        assert(!$this->fields_resolver, 'Do not set the fields resolver more than once');
        $this->fields_resolver = $resolver ?: self::$default_fields_resolver;
        return $this;
    }

    /**
     * Gets the field definition for the given key, if one is configured for
     * the class of $this.
     *
     * @param string $key Field key: array key in `static::getFields()`.
     *
     * @return Field|null The `Field`, or `null` if it doesn't exist.
     */
    private function getField(string $key): ?Field
    {
        return $this->fields_resolver->resolve(static::class)[$key] ?? null;
    }
}
