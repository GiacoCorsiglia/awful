<?php
namespace Awful\Providers;

use Awful\Cli\AwfulCommand;
use Awful\Models\Database\MultisiteDatabaseHooks;

class CoreProvider extends Provider
{
    public function commands(): array
    {
        return [
            AwfulCommand::class,
        ];
    }

    public function plugins(): array
    {
        return [
            MultisiteDatabaseHooks::class,
        ];
    }
}
