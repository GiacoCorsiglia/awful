<?php
namespace Awful\Models\Fields;

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

    /**
     * Validates and optionally modifies the value before it is saved.
     *
     * @param null|bool|int|float|string|array $value
     * @param Model $model
     *
     * @throws ValidationException
     *
     * @return mixed
     */
    abstract public function clean($value, Model $model);

    public function isRequired(): bool
    {
        return $this->args['required'];
    }

    /**
     * Prepares the field for serialization to be sent to the front-end.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['$type' => strtr(ltrim(static::class, '\\'), '\\', '.')] + $this->args;
    }

    /**
     * Filters the value of the field when it is loaded from the database by a
     * Model instance.
     *
     * @param null|bool|int|float|string|array $value The raw value as it is
     *                                                saved in the database.
     * @param Model $model
     * @param string $fieldKey
     *
     * @return mixed The filtered value.
     */
    abstract public function toPhp($value, Model $model, string $fieldKey);

    protected function extend(array $args): self
    {
        return new static($args + $this->args);
    }
}
