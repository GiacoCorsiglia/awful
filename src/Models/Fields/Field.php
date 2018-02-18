<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\HasFields;

/**
 * Base class for any field that can be saved on a HasFields object.
 */
abstract class Field
{
    /**
     * Field type as referred to internally by Advanced Custom Fields.
     *
     * @var string
     */
    protected const ACF_TYPE = '';

    /**
     * Associative array of default field configuration.  Shallow merge only.
     *
     * @var mixed[]
     */
    protected const DEFAULTS = [];

    /**
     * Associative array of field configuration.
     *
     * @var mixed[]
     */
    protected $args;

    /**
     * @param mixed[] $args Associative array of field configuration.
     */
    public function __construct(array $args = [])
    {
        $this->args = $args + static::DEFAULTS;
        // TODO: Potentially add lots of assertions to validate config.
    }

    /**
     * Filters the value of the field when it is loaded from the database by a
     * HasFields object.
     *
     * @param mixed     $value      The raw value as saved in the database.
     * @param HasFields $owner
     * @param string    $field_name
     *
     * @return mixed The filtered value.
     */
    abstract public function forPhp($value, HasFields $owner, string $field_name);

    /**
     * Filters the value of the field when it is loaded from the database for
     * display in the admin editor interface.
     *
     * @param mixed $value The raw value as saved in the database.
     *
     * @return mixed The filtered value.
     */
    public function forEditor($value)
    {
        return $value;
    }

    /**
     * Validates and optionally mutates the value before it is saved.
     *
     * @param mixed $value
     *
     * @throws ValidationException
     *
     * @return mixed
     */
    public function clean($value)
    {
        return $value;
    }

    /**
     * Converts the field to an array for registration with Advanced Custom
     * Fields and registers any filters with ACF.
     *
     * Due to filter registration, this method is not idempotent:  it expects to
     * be called just once, during the ACF registration phase.
     *
     * @param string         $name     Name of this field as it is saved on its
     *                                 owner.
     * @param string         $base_key Key of field parent (or post type, etc.).
     * @param FieldsResolver $resolver For resolving sub-fields, if needed.
     *
     * @return array Field definition for Advanced Custom Fields.
     */
    public function toAcf(string $name, string $base_key, FieldsResolver $resolver): array
    {
        $key = $this->buildAcfKey($name, $base_key, true);

        $acf = [
            'key' => $key,
            'name' => $name,
            'type' => static::ACF_TYPE,
        ] + $this->args;

        // We allow (require, in fact) conditional logic definitions to specify
        // field names instead of field keys, so we must convert those for ACF.
        if (!empty($acf['conditional_logic'])) {
            foreach ($acf['conditional_logic'] as &$condition_group) {
                foreach ($condition_group as &$condition) {
                    $condition['field'] = $this->buildAcfKey($condition['field'], $base_key, true);
                }
            }
        }

        // Add filters.
        $this->addAcfFilter('load_value', $key, [$this, 'acfLoadValueFilter'], 3);
        $this->addAcfFilter('validate_value', $key, [$this, 'acfValidateValueFilter'], 4);
        $this->addAcfFilter('update_value', $key, [$this, 'acfUpdateValueFilter'], 3);

        return $acf;
    }

    /**
     * Uses the `forEditor()` method to mutate the saved value before it is
     * displayed in the admin editor interface by ACF.
     *
     * @see https://www.advancedcustomfields.com/resources/acf-load_value/
     *
     * @param mixed      $value
     * @param int|string $post_id
     * @param array      $field
     *
     * @return mixed
     */
    final public function acfLoadValueFilter($value, $post_id, $field)
    {
        return $this->forEditor($value);
    }

    /**
     * Uses the `clean()` method to validate the value before it is saved by
     * ACF; if invalid, the save of the entire post will be aborted.
     *
     * @see https://www.advancedcustomfields.com/resources/acf-validate_value/
     *
     * @param bool|string $valid
     * @param mixed       $value
     * @param array       $field
     * @param string      $input
     *
     * @return bool|string
     */
    final public function acfValidateValueFilter($valid, $value, $field, $input)
    {
        if (!$valid || is_string($valid)) {
            return $valid;
        }

        try {
            $this->clean($value);
        } catch (ValidationException $e) {
            return $e->getMessage();
        }

        return $valid;
    }

    /**
     * Uses the `clean()` method to modify the value before it is saved to the
     * database by ACF.
     *
     * @see https://www.advancedcustomfields.com/resources/acf-update_value/
     *
     * @param mixed      $value
     * @param int|string $post_id
     * @param array      $field
     *
     * @return mixed
     */
    final public function acfUpdateValueFilter($value, $post_id, $field)
    {
        return $this->clean($value);
    }

    final protected function addAcfFilter(
        string $filter,
        string $field_key,
        callable $callable,
        int $args_count = 1
    ): void {
        add_filter("acf/$filter/key=$field_key", $callable, 10, $args_count);
    }

    /**
     * Converts a field name to an ACF field key.
     *
     * @param string $name     Field name.
     * @param string $base_key Key of field parent (or post type, etc.).
     * @param bool   $prefix   Whether to prefix with 'field_'.
     *
     * @return string Field key.
     */
    final protected function buildAcfKey(string $name, string $base_key, bool $prefix): string
    {
        // ACF default field key prefix: 'field_'
        // Separator for nested fields: '__'
        return ($prefix ? 'field_' : '') . $base_key . ($base_key ? '__' : '') . $name;
    }

    protected function extend(array $args): self
    {
        return new static($args + $this->args);
    }
}
