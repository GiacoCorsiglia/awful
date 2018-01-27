<?php
namespace Awful\Models\Fields;

/**
 * Base class for any fields that be saved on objects.
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
     * Associative array of default field configuration.
     *
     * @var mixed[]
     */
    protected const DEFAULTS = [];

    /**
     * Enumeration of supported hooks for this field type, with the name of the
     * hook as the array key.
     *
     * @var bool[]
     */
    protected const HOOKS = [
        'load_value' => true,
        'update_value' => true,
        'validate_value' => true,
    ];

    /**
     * ACF standard field key prefix.  Do not modify.
     * @var string
     */
    private const FIELD_KEY_PREFIX = 'field_';

    /**
     * Separator used for generating nested field keys.  Do not modify.
     * @var string
     */
    private const FIELD_KEY_SEPARATOR = '__';

    /**
     * Associative array of field configuration.
     *
     * @var mixed[]
     */
    protected $args;

    /**
     * Associative array of field hooks to register when registering this field;
     * callables keyed by hook name.
     *
     * @var callable[]
     */
    private $hooks;

    public function __construct(array $args = [], array $hooks = [])
    {
        $this->args = $args + static::DEFAULTS;

        // TODO: Potentially add lots more assertions for possible parameters.

        foreach ($hooks as $hook => $callable) {
            assert(!empty(static::HOOKS[$hook]), "Invalid hook: $hook");
            assert(is_callable($callable), 'Expected callable hook');
        }
    }

    public function toAcf(string $name, string $base_key = ''): array
    {
        $key = $this->keyify($name, $base_key, true);

        $acf = [
            'key' => $key,
            'name' => "$name",
            'type' => static::ACF_TYPE,
        ] + $this->args;

        // We allow (require, in fact) conditional logic definitions to specify
        // field names instead of field keys, so we must convert those for ACF.
        if (!empty($acf['conditional_logic'])) {
            foreach ($acf['conditional_logic'] as &$condition_group) {
                foreach ($condition_group as &$condition) {
                    $condition['field'] = $this->keyify($condition['field'], $base_key, true);
                }
            }
        }

        return $acf;
    }

    /**
     * Filters the value of the field when its loaded from the database.
     *
     * @param mixed     $value      The raw value.
     * @param HasFields $owner
     * @param string    $field_name
     *
     * @return mixed The filtered value.
     */
    abstract public function toPhp($value, HasFields $owner, string $field_name);

    protected function extend(array $args, array $hooks): self
    {
        return new static($args + $this->args, $hooks + $this->hooks);
    }

    /**
     * Converts a field name to an ACF field key.
     *
     * @param string $name     Field name.
     * @param string $base_key Key of field parent (or post type, etc.).
     * @param bool   $prefix   Whether to prefix with the `FIELD_KEY_PREFIX`.
     *
     * @return string Field key.
     */
    final protected function keyify(string $name, string $base_key, bool $prefix): string
    {
        return ($prefix ? self::FIELD_KEY_PREFIX : '')
            . $base_key
            . ($base_key ? self::FIELD_KEY_SEPARATOR : '')
            . $name;
    }
}
