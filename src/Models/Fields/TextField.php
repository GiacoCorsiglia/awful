<?php
namespace Awful\Models\Fields;

use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

/**
 * A single- or multi-line text field.
 *
 * The 'regex' accepts a regular expression to validate the input against.  If
 * it is also a valid JavaScript regex, it will be used for client-side
 * validation as well.
 */
class TextField extends Field
{
    /**
     * <input type="text" /> widget type.
     * @var string
     */
    public const INPUT_WIDGET = 'input';

    /**
     * <textarea> widget type.
     * @var string
     */
    public const TEXTAREA_WIDGET = 'textarea';

    protected const DEFAULTS = [
        'default_value' => '',
        'minlength' => 0,
        'maxlength' => 0,
        'regex' => '',
        'widget' => self::INPUT_WIDGET,
    ] + Field::DEFAULTS;

    public function __construct(array $args = [])
    {
        parent::__construct($args);

        assert(is_int($this->args['minlength']) && $this->args['minlength'] >= 0, "Expected positive integer for 'minlength'.");
        assert(is_int($this->args['maxlength']) && $this->args['maxlength'] >= 0, "Expected positive integer for 'maxlength'.");
        assert($this->args['maxlength'] >= $this->args['minlength'], "Expected 'maxlength' >= 'minlength'.");
        assert(is_string($this->args['regex']), "Expected a string for 'regex'.");
        assert(in_array($this->args['widget'], [self::INPUT_WIDGET, self::TEXTAREA_WIDGET]), "Expected supported 'widget'.");
    }

    public function clean($value, Model $model): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new ValidationException('Expected a string.');
        }

        $length = strlen($value);
        $min = $this->args['minlength'];
        $max = $this->args['maxlength'];
        if ($min && $length < $min) {
            throw new ValidationException("Requires at least $min characters.");
        }
        if ($max && $length > $max) {
            throw new ValidationException("May be at most $max characters.");
        }

        if ($this->args['regex'] && !preg_match($this->args['regex'], $value)) {
            throw new ValidationException('Invalid input.');
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
