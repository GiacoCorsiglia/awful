<?php
namespace AwfulExample;

use Awful\Models\Fields\TextField;

class Post extends \Awful\Models\Post
{
    protected static function registerFields(): array
    {
        return [
            'field1' => new TextField(),
            'field2' => new TextField(['maxlength' => 25]),
        ];
    }
}
