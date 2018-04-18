<?php
namespace Awful\Exceptions;

use Exception;

/**
 * Exception thrown when trying to mutate something intended to be immutable.
 */
class ImmutabilityException extends Exception
{
    public function __construct(string $class = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("$class objects are immutable.", $code, $previous);
    }
}
