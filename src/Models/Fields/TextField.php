<?php
namespace Awful\Models\Fields;

use Awful\Models\HasFields;

/**
 * A single line text field.
 */
class TextField extends Field
{
    const ACF_TYPE = 'text';

    public function forPhp($value, HasFields $owner, string $field_name): string
    {
        if (is_array($value)) {
            // Avoid "Array to string conversion" warning.  Could return
            // $value[0], but maybe that's too magic.
            return '';
        }

        return (string) $value;
    }
}
