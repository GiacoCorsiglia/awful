<?php
namespace Awful\Fields;

/**
 * A single line text field.
 */
class TextField extends Field
{
    const ACF_TYPE = 'text';

    public function forPhp($value): string
    {
        return (string) $value;
    }
}
