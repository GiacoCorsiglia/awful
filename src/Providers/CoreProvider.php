<?php
namespace Awful\Providers;

use Awful\Cli\AwfulCommand;

class CoreProvider extends Provider
{
    public function commands(): array
    {
        return [
            AwfulCommand::class,
        ];
    }
}
