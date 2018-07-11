<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

/**
 * A field that accepts any numeric values within the bounds of what PHP can
 * process as floating point numbers.
 *
 * If you need exact precision (e.g., for monetary values), use a TextField with
 * the regular expression `/^\d*(?:\.\d+)?$/` instead.
 */
class DecimalField extends Field
{
    protected const DEFAULTS = [
        'min' => -PHP_FLOAT_MAX,
        'max' => PHP_FLOAT_MAX,
    ] + Field::DEFAULTS;

    public function __construct(array $args = [])
    {
        parent::__construct($args);

        assert($this->args['min'] === null || is_float($this->args['min']) || is_int($this->args['min']), "Expected positive float for 'min'.");
        assert($this->args['max'] === null || is_float($this->args['max']) || is_int($this->args['max']), "Expected positive float for 'max'.");
        assert(
            $this->args['min'] === null || $this->args['max'] === null || $this->args['max'] >= $this->args['min'],
            "Expected 'max' >= 'min'."
        );
    }

    public function clean($value, Model $model): ?float
    {
        if ($value === null) {
            return null;
        }

        if ((!is_int($value) && !is_float($value)) || is_nan($value)) {
            throw new ValidationException('Expected a number.');
        }

        $min = $this->args['min'];
        if ($min !== null && $value < $min) {
            throw new ValidationException("Must be greater than or equal to $min.");
        }
        $max = $this->args['max'];
        if ($max !== null && $value > $max) {
            throw new ValidationException("Must be less than or equal to $max.");
        }

        return (float) $value;
    }

    public function toPhp($value, Model $model, string $fieldKey): ?float
    {
        if (!is_numeric($value)) {
            // This includes the `$value === null` case.
            return null;
        }

        return (float) $value;
    }
}
