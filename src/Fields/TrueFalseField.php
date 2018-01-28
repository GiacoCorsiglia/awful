<?php
namespace Awful\Fields;

/**
 * A field that represents a boolean choice.
 */
class TrueFalseField
{
    const ACF_TYPE = 'true_false';

    public function forPhp($value): bool
    {
        return (bool) $value;
    }
}
