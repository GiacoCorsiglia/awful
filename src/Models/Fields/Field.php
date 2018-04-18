<?php
namespace Awful\Models\Fields;

use Awful\Models\BlockOwnerModel;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;
use JsonSerializable;

/**
 * Base class for any field that can be saved on a Model object.
 */
abstract class Field implements JsonSerializable
{
    /**
     * Associative array of default field configuration.  Shallow merge only.
     *
     * @var mixed[]
     */
    protected const DEFAULTS = [
        'required' => false,
    ];

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

    public function isRequired(): bool
    {
        return $this->args['required'];
    }

    /**
     * Filters the value of the field when it is loaded from the database by a
     * BlockOwnerModel object.
     *
     * @param mixed  $value      The raw value as saved in the database.
     * @param Model  $owner
     * @param string $field_name
     *
     * @return mixed The filtered value.
     */
    abstract public function forPhp($value, Model $owner, string $field_name);

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
     * Prepares the field for serialization to be sent to the front-end.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->args;
    }

    protected function extend(array $args): self
    {
        return new static($args + $this->args);
    }
}
