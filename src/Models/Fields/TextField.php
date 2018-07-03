<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

/**
 * A single line text field.
 */
class TextField extends Field
{
    public function clean($value, Model $model): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new ValidationException('Expected a string.');
        }

        return $value;
    }

    public function toPhp($value, Model $model, string $fieldKey): string
    {
        if (is_array($value)) {
            // Avoid "Array to string conversion" warning.  Could return
            // $value[0], but maybe that's too magic.
            return '';
        }

        return (string) $value;
    }
}
