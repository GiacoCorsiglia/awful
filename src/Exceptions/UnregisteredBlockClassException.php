<?php
namespace Awful\Exceptions;

use Exception;

class UnregisteredBlockClassException extends Exception
{
    public function __construct(string $class = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Unregistered block class: '$class'.", $code, $previous);
    }
}
