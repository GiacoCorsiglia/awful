<?php
namespace Awful\Models;

use Awful\Container\Container;
use Awful\Models\Fields\Field;
use Awful\Models\Fields\FieldsResolver;

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

    /**
     * The `FieldsResolver` to use when resolving fields on this instance and
     * related objects.
     *
     * @var FieldsResolver
     */
    private $fields_resolver;

    /**
     * Cached field values.
     *
     * @var mixed[]
     */
    private $filtered_data_cache = [];

    /**
     * Returns the `FieldsResolver` to use when resolving fields on this
     * instance and related objects like `SubModel` instances or parent or child
     * `Model` instances.
     *
     * @final Want to really make this final, but it makes it untestable.
     *
     * @return FieldsResolver
     */
    public function getFieldsResolver(): FieldsResolver
    {
        return $this->fields_resolver;
    }

    /**
     * Fetches the value of the field saved on this object, and tries to cast it
     * to the correct format for its corresponding field definition (if any).
     *
     * Pass `true` for `$raw` to avoid an infinite loop if called within a
     * class' own `getFields()` method.
     *
     * @param string $key The name of the field; i.e., its key in the array
     *                    returned by getFields()
     *
     * @return mixed The value of the field, or null if unset.
     */
    final public function get(string $key)
    {
        assert((bool) $key, 'Expected non-empty $key');

        if (isset($this->filtered_data_cache[$key])) {
            return $this->filtered_data_cache[$key];
        }

        $value = $this->getRawFieldValue($key);

        if ($field = $this->getField($key)) {
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
    abstract public function getRawFieldValue(string $key);

    abstract public function getDataSource(): ?self;

    abstract public function getDataPrefix(): string;

    /**
     * Sets the FieldsResolver instance to use when resolving field definitions
     * for this instance.
     *
     * @param FieldsResolver|null $resolver If null, the default will be used.
     *
     * @return void
     *
     * @internal Exposed for use internally by Awful, or for explicit testing.
     */
    final protected function initializeFieldsResolver(FieldsResolver $resolver = null): void
    {
        assert(!$this->fields_resolver, 'Do not set the fields resolver more than once');
        $this->fields_resolver = $resolver ?: self::$default_fields_resolver;
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
