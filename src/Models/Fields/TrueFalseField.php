<?php
namespace Awful\Models\Fields;

use Awful\Models\HasFields;

/**
 * A field that represents a boolean choice.
 */
class TrueFalseField extends Field
{
    const ACF_TYPE = 'true_false';

    public function forPhp($value, HasFields $owner, string $field_name): bool
    {
        return (bool) $value;
    }
}
