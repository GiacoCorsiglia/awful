<?php
namespace AwfulExample;

use Awful\Providers\Provider;

class ExampleProvider extends Provider
{
    public function plugins(): array
    {
        return [
            ExamplePlugin::class,
        ];
    }
}
