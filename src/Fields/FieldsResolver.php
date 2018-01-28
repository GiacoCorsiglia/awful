<?php
namespace Awful\Fields;

use Awful\Container\Container;

/**
 * Utility to resolve the fields defined on a `HasFields` subclass.
 */
final class FieldsResolver
{
    /** @var Container */
    private $container;

    /**
     * Cache of resolved fields keyed by class name.
     *
     * @var (Field[])[]
     */
    private $cache = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Given a subclass of `HasFields`, returns the array of fields defined in
     * that class' `getFields()` method, injecting dependencies as needed.
     *
     * @param string $has_fields_subclass Name of a `HasFields` subclass.
     *
     * @return Field[] Field definitions from the `HasFields` subclass.
     */
    public function resolve(string $has_fields_subclass): array
    {
        // It's potentially expensive to generate a bunch of `Field` instances
        // repeatedly (including anything `HasFields::get()` is called), so
        // we'll cache the generated fields by class.
        if (isset($this->cache[$has_fields_subclass])) {
            return $this->cache[$has_fields_subclass];
        }

        assert(!empty(class_parents($has_fields_subclass)[HasFields::class]), 'Expected HasFields subclass');

        // Load the fields.
        $fields = $has_fields_subclass::getFields();
        // Allow `getFields()` to return an injectable callable.
        if (is_callable($fields)) {
            $fields = $this->container->call($fields);
        }

        assert(is_array($fields) && !array_filter($fields, function ($field, $name) {
            return !is_string($name) || !($field instanceof Field);
        }, ARRAY_FILTER_USE_BOTH), "Expected $has_fields_subclass::getFields() to resolve to an associative array of Field instances.");

        // Set the cache for next time and return the fields now.
        return $this->cache[$has_fields_subclass] = $fields;
    }
}
