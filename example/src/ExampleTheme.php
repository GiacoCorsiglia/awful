<?php
namespace AwfulExample;

use Awful\Theme\Theme;

class ExampleTheme extends Theme
{
    public function postTypes(): array
    {
        return [
            'post' => Post::class,
        ] + parent::postTypes();
    }
}
