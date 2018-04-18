<?php
namespace Awful\Models\Fields;

use Awful\Models\Block;
use Awful\Models\Field\BlocksFieldInstance;
use function Awful\every;

class BlocksField extends Field
{
    public function __construct(array $args = [])
    {
        assert(every($args['types'], 'is_subclass_of', [Block::class]));

        parent::__construct($args);
    }

    public function forPhp($value, Model $owner, string $fieldName)
    {

        return new BlocksFieldInstance((array) $value, $owner, $fieldName);
    }
}
