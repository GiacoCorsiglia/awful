<?php
namespace Awful\Models\Database\Map\Exceptions;

use Exception;

class UnknownTypeException extends Exception
{
    public function __construct(string $type = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct("Unknown type in map: '$type'.", $code, $previous);
    }
}
