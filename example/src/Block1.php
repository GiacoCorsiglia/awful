<?php
namespace AwfulExample;

use Awful\Models\Block;
use Awful\Models\Fields\TextField;

class Block1 extends Block
{
    const LABEL = 'Block Type 1';

    public static function registerFields(): array
    {
        return [
            'text' => new TextField(),
        ];
    }
}
