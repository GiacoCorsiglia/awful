<?php
namespace Awful\Cli;

abstract class Command
{
    /** @var string */
    const COMMAND_NAME = '';

    public static function commandName(): string
    {
        assert((bool) static::COMMAND_NAME, 'Expected COMMAND_NAME constant to be defined');
        return static::COMMAND_NAME;
    }

    public static function registrationArguments(): array
    {
        return [];
    }
}
