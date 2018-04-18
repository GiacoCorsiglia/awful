<?php
namespace Awful\Models\Fields;

use Awful\Models\Model;

/**
 * A single line text field.
 */
class TextField extends Field
{
    public function forPhp($value, Model $owner, string $field_name): string
    {
        if (is_array($value)) {
            // Avoid "Array to string conversion" warning.  Could return
            // $value[0], but maybe that's too magic.
            return '';
        }

        return (string) $value;
    }
}
