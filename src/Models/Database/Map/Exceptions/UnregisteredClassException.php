<?php
namespace Awful\Models\Database\Map\Exceptions;

use Exception;

class UnregisteredClassException extends Exception
{
    public function __construct(string $class = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Unregistered class in map: '$class'.", $code, $previous);
    }
}
