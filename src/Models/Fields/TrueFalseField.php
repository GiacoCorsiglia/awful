<?php
namespace Awful\Models\Fields;

use Awful\Models\Model;

/**
 * A field that represents a boolean choice.
 */
class TrueFalseField extends Field
{
    const ACF_TYPE = 'true_false';

    public function forPhp($value, Model $owner, string $field_name): bool
    {
        return (bool) $value;
    }
}
