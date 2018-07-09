<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

/**
 * A field that accepts integral values within PHP's integer bounds.
 *
 * If you need arbitrarily large or small integers, use a TextField with the
 * regular expression `/^\d*$/` instead.
 */
class IntegerField extends Field
{
    protected const DEFAULTS = Field::DEFAULTS + [
        'min' => PHP_INT_MIN,
        'max' => PHP_INT_MAX,
    ];

    public function __construct(array $args = [])
    {
        parent::__construct($args);

        assert($this->args['min'] === null || is_int($this->args['min']), "Expected positive integer for 'min'.");
        assert($this->args['max'] === null || is_int($this->args['max']), "Expected positive integer for 'max'.");
        assert(
            $this->args['min'] === null || $this->args['max'] === null || $this->args['max'] >= $this->args['min'],
            "Expected 'max' >= 'min'."
        );
    }

    public function clean($value, Model $model): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!is_int($value)) {
            throw new ValidationException('Expected an integer.');
        }

        $min = $this->args['min'];
        if ($min !== null && $value < $min) {
            throw new ValidationException("Must be greater than or equal to $min.");
        }
        $max = $this->args['max'];
        if ($max !== null && $value > $max) {
            throw new ValidationException("Must be less than or equal to $max.");
        }

        return $value;
    }

    public function toPhp($value, Model $model, string $fieldKey): ?int
    {
        if (!is_numeric($value)) {
            // This includes the `$value === null` case.
            return null;
        }

        return (int) $value;
    }
}
