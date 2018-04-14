<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\HasFields;
use JsonSerializable;

/**
 * Base class for any field that can be saved on a HasFields object.
 */
abstract class Field implements JsonSerializable
{
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
