<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

/**
 * A field that represents a boolean choice.
 */
class TrueFalseField extends Field
{
    public function toPhp($value, Model $model, string $fieldKey): bool
    {
        return (bool) $value;
    }

    public function clean($value, Model $model): ?bool
    {
        if ($value === null) {
            return $value;
        }

        if (!is_bool($value)) {
            throw new ValidationException('Expected a boolean.');
        }

        return $value;
    }
}
